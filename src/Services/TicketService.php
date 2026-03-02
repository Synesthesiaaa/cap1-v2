<?php

namespace Services;

use Repositories\TicketRepository;
use Services\LogService;
use Services\Logger;
use Database\Connection;
require_once __DIR__ . '/../../config/sla_automation_rules.php';

/**
 * Ticket Service
 * 
 * Business logic for ticket operations
 */
class TicketService
{
    private $ticketRepository;
    private $logService;
    private $logger;
    private $conn;

    public function __construct()
    {
        $this->ticketRepository = new \Repositories\TicketRepository();
        $this->logService = new LogService();
        $this->logger = Logger::getInstance();
        $this->conn = Connection::getInstance()->getConnection();
    }

    /**
     * Create a new ticket
     */
    public function create(array $data, int $userId, string $userRole): array
    {
        try {
            $category = $data['category'] ?? '';
            $type = $data['type'] ?? $data['ticket_type'] ?? '';
            $userType = $data['user_type'] ?? 'internal';
            $slaResult = null;

            // SLA Weight: compute priority and auto-assign
            if ($category && $type) {
                $slaResult = $this->computeSlaPriority($category, $type, $userType);
                if ($slaResult && !empty($slaResult['sla_weight'])) {
                    $data['priority'] = $slaResult['priority'];
                    if (!empty($slaResult['matched_sla_weight_id']) && $this->ticketColumnExists('sla_weight_id')) {
                        $data['sla_weight_id'] = (int)$slaResult['matched_sla_weight_id'];
                    }
                    if (isset($slaResult['priority_score']) && $this->ticketColumnExists('sla_priority_score')) {
                        $data['sla_priority_score'] = (float)$slaResult['priority_score'];
                    }
                    if (!isset($data['assigned_technician_id']) && $slaResult['department_name']) {
                        $data['_sla_department'] = $slaResult['department_name'];
                    }
                    $data['_sla_priority_score'] = (float)$slaResult['priority_score'];
                }
            }

            // Fallback: calculate priority from routing if not set
            if (!isset($data['priority'])) {
                $data['priority'] = $this->calculatePriority($data, $category);
            }

            // Urgent override
            if (isset($data['is_urgent']) && $data['is_urgent'] == '1') {
                $data['priority'] = 'urgent';
            }

            // Generate reference ID
            $referenceId = $this->generateReferenceId();
            $data['reference_id'] = $referenceId;
            $data['user_id'] = $userId;
            $data['created_at'] = date('Y-m-d H:i:s');

            // Calculate SLA date based on priority
            if (!isset($data['sla_date'])) {
                $data['sla_date'] = $this->calculateSlaDate($data['priority']);
            }

            // Auto-assign technician if needed (SLA weight or routing)
            if (!isset($data['assigned_technician_id']) || $data['assigned_technician_id'] === null) {
                $data['assigned_technician_id'] = $this->autoAssignTechnician($data);
            }

            // Set status
            if (!isset($data['status'])) {
                $data['status'] = $data['assigned_technician_id'] ? 'pending' : 'assigning';
            }

            $checklistOptions = [
                'priority' => $data['priority'] ?? 'low',
                'priority_score' => $data['_sla_priority_score'] ?? ($data['sla_priority_score'] ?? null),
                'user_type' => $userType,
                'sla_weight_id' => $data['sla_weight_id'] ?? null,
                'normalized_category' => $slaResult['normalized_category'] ?? $category,
            ];

            // Remove internal flags and non-DB fields before insert
            unset(
                $data['_sla_department'],
                $data['_sla_priority_score'],
                $data['is_urgent'],
                $data['user_type'],
                $data['ticket_type']
            );

            // Create ticket
            $ticketId = $this->ticketRepository->create($data);

            if ($ticketId) {
                // Log the creation
                $this->logService->logTicketAction(
                    $ticketId,
                    $userId,
                    $userRole,
                    'created',
                    "Ticket created: {$data['title']}"
                );

                // Update technician active tickets count
                if ($data['assigned_technician_id']) {
                    $this->updateTechnicianTicketCount($data['assigned_technician_id'], 1);
                }

                // Auto-generate checklist if needed
                if (isset($data['category'])) {
                    $this->autoGenerateChecklist($ticketId, $data['category'], $data['type'] ?? null, $checklistOptions);
                }

                $this->refreshSummaryForTicket((int)$ticketId, $userId);

                $this->logger->info("Ticket created successfully", [
                    'ticket_id' => $ticketId,
                    'reference_id' => $referenceId,
                    'user_id' => $userId
                ]);

                return [
                    'success' => true,
                    'ticket_id' => $ticketId,
                    'reference_id' => $referenceId
                ];
            }

            throw new \Exception("Failed to create ticket");

        } catch (\Exception $e) {
            $this->logger->error("Failed to create ticket", [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to create ticket'
            ];
        }
    }

    /**
     * Update ticket
     */
    public function update(int $ticketId, array $data, int $userId, string $userRole): bool
    {
        try {
            $ticket = $this->ticketRepository->findById($ticketId);
            if (!$ticket) {
                throw new \Exception("Ticket not found");
            }

            // Update ticket
            $result = $this->ticketRepository->update($ticketId, $data);

            if ($result) {
                // Log the update
                $this->logService->logTicketAction(
                    $ticketId,
                    $userId,
                    $userRole,
                    'updated',
                    "Ticket updated: " . json_encode($data)
                );

                $this->logger->info("Ticket updated", [
                    'ticket_id' => $ticketId,
                    'user_id' => $userId
                ]);

                $this->refreshSummaryForTicket($ticketId);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logger->error("Failed to update ticket", [
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Get ticket by reference ID
     */
    public function getByReference(string $referenceId): ?array
    {
        return $this->ticketRepository->findByReference($referenceId);
    }

    /**
     * Get tickets with filters
     */
    public function getTickets(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        return $this->ticketRepository->getTickets($filters, $page, $perPage);
    }

    /**
     * Resolve ticket
     */
    public function resolve(int $ticketId, int $userId, string $userRole, ?string $resolutionNotes = null): bool
    {
        try {
            $ticket = $this->ticketRepository->findById($ticketId);
            if (!$ticket) {
                throw new \Exception("Ticket not found");
            }

            $updateData = [
                'status' => 'complete',
                'resolved_at' => date('Y-m-d H:i:s')
            ];

            if ($resolutionNotes) {
                $updateData['resolution_notes'] = $resolutionNotes;
            }

            $result = $this->ticketRepository->update($ticketId, $updateData);

            if ($result) {
                // Log the resolution
                $this->logService->logTicketAction(
                    $ticketId,
                    $userId,
                    $userRole,
                    'resolved',
                    $resolutionNotes ?? "Ticket resolved"
                );

                // Update technician active tickets count
                if ($ticket['assigned_technician_id']) {
                    $this->updateTechnicianTicketCount($ticket['assigned_technician_id'], -1);
                }

                $this->logger->info("Ticket resolved", [
                    'ticket_id' => $ticketId,
                    'user_id' => $userId
                ]);

                $this->refreshSummaryForTicket($ticketId);
            }

            return $result;

        } catch (\Exception $e) {
            $this->logger->error("Failed to resolve ticket", [
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Compute priority from SLA Weight table (auto-assigns)
     */
    private function computeSlaPriority(string $category, string $type, string $userType): ?array
    {
        try {
            if (class_exists(\Services\SlaWeightService::class)) {
                $slaService = new SlaWeightService();
                $result = $slaService->computePriorityScore($category, $type, $userType);
                if ($result['sla_weight']) {
                    return $result;
                }
                $this->logger->warning("No SLA mapping found for ticket create", [
                    'type' => $type,
                    'category' => $category,
                    'normalized_category' => $result['normalized_category'] ?? $category,
                    'user_type' => $userType
                ]);
            }
        } catch (\Exception $e) {
            $this->logger->warning("SLA Weight lookup failed", ['error' => $e->getMessage()]);
        }
        return null;
    }

    /**
     * Calculate priority based on urgency and category routing
     */
    private function calculatePriority(array $postData, ?string $category = null): string
    {
        // If user marked urgent, override everything
        if (isset($postData['is_urgent']) && $postData['is_urgent'] == '1') {
            return 'urgent';
        }

        // Check category routing for priority boost
        if ($category) {
            try {
                $sql = "SELECT priority_boost FROM tbl_department_routing WHERE category = ? LIMIT 1";
                $stmt = $this->conn->prepare($sql);
                if ($stmt) {
                    $stmt->bind_param("s", $category);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $priorityBoost = $row['priority_boost'];
                        switch ($priorityBoost) {
                            case 'high':
                                return 'high';
                            case 'medium':
                                return 'regular';
                            case 'low':
                            case 'none':
                            default:
                                return 'low';
                        }
                    }
                    $stmt->close();
                }
            } catch (\Exception $e) {
                $this->logger->warning("Failed to get priority boost from routing", [
                    'category' => $category,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return $postData['priority'] ?? 'low';
    }

    /**
     * Calculate SLA date based on priority
     */
    private function calculateSlaDate(string $priority): string
    {
        $days = \slaPrioritySlaDays($priority);
        return date('Y-m-d', strtotime("+{$days} days"));
    }

    /**
     * Auto-assign technician based on SLA weight or department routing
     */
    private function autoAssignTechnician(array $ticketData): ?int
    {
        try {
            $type = $ticketData['type'] ?? $ticketData['ticket_type'] ?? '';
            $category = $ticketData['category'] ?? '';

            // SLA Weight: use department from SLA table for auto-assign
            if (!empty($ticketData['_sla_department'])) {
                $deptType = $ticketData['_sla_department'];
                $techId = $this->findAvailableTechnician($deptType);
                if ($techId) {
                    return $techId;
                }
            }

            // Fallback: Check department routing
            $sql = "SELECT target_department_id, auto_assign_technician 
                    FROM tbl_department_routing 
                    WHERE category = ? 
                    LIMIT 1";
            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("s", $category);
                $stmt->execute();
                $result = $stmt->get_result();
                $routing = $result->fetch_assoc();
                $stmt->close();

                if ($routing && $routing['auto_assign_technician']) {
                    return $this->findAvailableTechnician($type, $routing['target_department_id']);
                }
            }

            return $this->findAvailableTechnician($type);

        } catch (\Exception $e) {
            $this->logger->error("Failed to auto-assign technician", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Find available technician
     */
    private function findAvailableTechnician(string $type, ?int $departmentId = null): ?int
    {
        try {
            if (in_array($type, ['IT'])) {
                $sql = "SELECT technician_id FROM tbl_technician
                        WHERE status = 'active'
                        AND specialization IN ('software', 'hardware')
                        ORDER BY active_tickets ASC LIMIT 1";
            } elseif (in_array($type, ['Facilities', 'Warehouse', 'Production', 'Engineering', 'Finance', 'HR', 'Sales', 'Shipping'])) {
                $sql = "SELECT technician_id FROM tbl_technician
                        WHERE status = 'active'
                        AND specialization = 'operation'
                        ORDER BY active_tickets ASC LIMIT 1";
            } else {
                $sql = "SELECT technician_id FROM tbl_technician
                        WHERE status = 'active'
                        ORDER BY active_tickets ASC LIMIT 1";
            }

            $result = $this->conn->query($sql);
            if ($result && $row = $result->fetch_assoc()) {
                return (int)$row['technician_id'];
            }

            return null;

        } catch (\Exception $e) {
            $this->logger->error("Failed to find available technician", [
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Update technician active ticket count
     */
    private function updateTechnicianTicketCount(int $technicianId, int $delta): void
    {
        try {
            $sql = "UPDATE tbl_technician 
                    SET active_tickets = GREATEST(0, active_tickets + ?) 
                    WHERE technician_id = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ii", $delta, $technicianId);
                $stmt->execute();
                $stmt->close();
            }
        } catch (\Exception $e) {
            $this->logger->error("Failed to update technician ticket count", [
                'technician_id' => $technicianId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Generate unique reference ID
     */
    private function generateReferenceId(): string
    {
        // Use existing reference generator if available
        if (file_exists(__DIR__ . '/../../utils/reference_generator.php')) {
            require_once __DIR__ . '/../../utils/reference_generator.php';
            if (function_exists('generateReferenceID')) {
                return generateReferenceID($this->conn);
            }
        }

        // Fallback: generate simple reference
        return 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    }

    /**
     * Auto-generate checklist from template
     */
    private function autoGenerateChecklist(int $ticketId, string $category, ?string $type, array $options = []): void
    {
        try {
            $script = __DIR__ . '/../../php/auto_generate_checklist.php';
            if (!file_exists($script)) {
                return;
            }

            require_once $script;
            if (!function_exists('autoGenerateChecklist')) {
                return;
            }

            $departmentId = null;
            if (!empty($type)) {
                $deptMap = [
                    'Human Resource' => 'HR',
                ];
                $departmentName = $deptMap[$type] ?? $type;

                $stmt = $this->conn->prepare("SELECT department_id FROM tbl_department WHERE department_name = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param("s", $departmentName);
                    $stmt->execute();
                    $row = $stmt->get_result()->fetch_assoc();
                    $stmt->close();
                    if ($row) {
                        $departmentId = (int)$row['department_id'];
                    }
                }
            }

            autoGenerateChecklist($this->conn, $ticketId, $category, $departmentId, $options);
        } catch (\Throwable $e) {
            $this->logger->warning("Checklist generation failed", [
                'ticket_id' => $ticketId,
                'category' => $category,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Refresh per-user customer summary after ticket mutation.
     */
    private function refreshSummaryForTicket(int $ticketId, ?int $userId = null): void
    {
        try {
            require_once __DIR__ . '/../../php/customer_summary_refresh.php';

            if ($userId !== null && function_exists('refreshUserTicketSummary')) {
                \refreshUserTicketSummary((int)$userId, $this->conn);
                return;
            }

            if (function_exists('refreshTicketSummaryByTicketId')) {
                \refreshTicketSummaryByTicketId((int)$ticketId, $this->conn);
            }
        } catch (\Throwable $e) {
            $this->logger->warning("Failed to refresh customer summary", [
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check whether tbl_ticket has a given column.
     */
    private function ticketColumnExists(string $column): bool
    {
        static $cache = [];
        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }

        try {
            $stmt = $this->conn->prepare("SHOW COLUMNS FROM tbl_ticket LIKE ?");
            if (!$stmt) {
                $cache[$column] = false;
                return false;
            }
            $stmt->bind_param("s", $column);
            $stmt->execute();
            $res = $stmt->get_result();
            $exists = $res && $res->num_rows > 0;
            $stmt->close();
            $cache[$column] = $exists;
            return $exists;
        } catch (\Throwable $e) {
            $cache[$column] = false;
            return false;
        }
    }
}
