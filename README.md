## ✨ Feature: Date Range Filtering for Horizon Job Lists

This PR adds support for filtering jobs in the Laravel Horizon dashboard by a **date range**, applicable to the following job lists:

- Pending
- Completed
- Silenced

---

### 🔍 Motivation

Operations and developers often need to inspect job execution logs within a specific timeframe (e.g. during a deployment, incident window, etc.). This feature improves traceability and helps narrow down job lists to relevant time periods.

---

### 🛠 What’s Included

- ✅ UI enhancement: "From" and "To" date inputs added to relevant job views
- ✅ Backend update: Filtering logic added to job queries based on `created_at`
- ✅ Non-breaking: If no date is selected, full job list remains visible (default behavior)
- ✅ Tests: Unit test(s) included to validate filtering behavior

---

### 🧪 How to Test

1. Open Horizon dashboard
2. Navigate to **Pending**, **Completed**, or **Silenced** tabs
3. Select a `from` and `to` date range
4. Observe the filtered results
5. Clear the filters to return to the full list

---

### 📸 Screenshots (if applicable)

_Add screenshots here if you have visual changes._

---

### ✅ Notes

This is fully backward-compatible and should not interfere with any existing Horizon behavior.


Laravel Horizon is open-sourced software licensed under the [MIT license](LICENSE.md).
