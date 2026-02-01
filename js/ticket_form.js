function updateCategory() {
    const type = document.getElementById("ticket_type").value;
    const categoryWrapper = document.getElementById("category-wrapper");
    const categorySelect = document.getElementById("category");

    categorySelect.innerHTML = '<option value="">-- Select Category --</option>';
    if (!type) {
        categoryWrapper.style.display = "none";
        return;
    }

    categoryWrapper.style.display = "block";
    // Include External and internal categories based on type selected and role of user
    let categories = [];
    if (type === "IT") {
        categories = [
            "System Access or Login Issues",
            "Network or router troubleshooting",
            "Hardware or software installation",
            "Malfunctioning PCs or peripherals",
            "Email configuration errors",
            "Coordination with other departments",
            "Other"
        ];
    } else if (type === "Finance") {
        categories = [
            "ERP entry errors",
            "Biling or reconciliation disputs",
            "Payment verification issues",
            "Report Generation errors",
            "Finandial data sync issues",
            "Other"
        ];
    } else if (type === "Engineering") {
        categories = [
            "Warranty validation errors",
            "Delayed ticket for servicing items",
            "Product serial verification",
            "Apprval for replacement items",
            "Other"
        ];
    } else if (type === "HR") {
        categories = [
            "Onboarding or Offboarding system access",
            "Employee account creation",
            "Password recovery",
            "Attendance record discrepancies",
            "Other"
        ];
    } else if (type === "Warehouse") {
        categories = [
            "Inventory record inconsistencies",
            "Missing stock entries",
            "Damaged item reports",
            "Delayed shipment arrivals",
            "Other"
        ];
    } else if (type === "Production") {
        categories = [
            "Batch tagging errors",
            "System synchronization lag",
            "Staff scheduling module malfunction",
            "Equipment maintenance",
            "Other"
        ];
    } else if (type === "Sales") {
        categories = [
            "Customer inquiry updates",
            "Warranty record assistance",
            "System-generated report errors",
            "Customer profile updates",
            "Other"
        ];
    }else if (type === "Shipping") {
        categories = [
            "Wrong delivery or Update issues",
            "Missing items",
            "Delivery Confirmation requests",
            "Logistics coordination",
            "Other"
        ];
    }else if (type === "Facilities") {
        categories = [
            
            "Furniture",
            "Lighting",
            "Plumbing",
            "Air Conditioning",
            "Renovation",
            "Electrical",
            "Other"
        ];
    }


    // The rest of the categories can be added here similarly

    categories.forEach(cat => {
        const opt = document.createElement("option");
        opt.value = cat;
        opt.textContent = cat;
        categorySelect.appendChild(opt);
    });
}

// function updateSLAInfo() {
//     const priority = document.getElementById("priority").value;
//     const slaInfo = document.getElementById("sla-info");

//     if (!priority) {
//         slaInfo.textContent = "SLA information will appear here.";
//         return;
//     }

//     switch (priority) {
//         case "low":
//             slaInfo.textContent = "Low Priority: SLA completion within 7 days.";
//             break;
//         case "medium":
//             slaInfo.textContent = "Medium Priority: SLA completion within 2 days.";
//             break;
//         case "high":
//         case "urgent":
//             slaInfo.textContent = "High/Urgent Priority: SLA completion within the same day.";
//             break;
//     }
// }
