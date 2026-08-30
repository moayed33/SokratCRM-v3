# Employee Workflows — SokratCRM

This document describes the day-to-day workflow of every employee type in SokratCRM. Roles are defined by **permission groups**; a single user can belong to multiple groups, and permissions are cumulative.

---

## 1. System Administrator (مدير النظام)

**Group code:** `super-admin`
**Scope:** Full unrestricted access to every module, every branch, every lead.

### Daily workflow

1. **Dashboard** — Review organization-wide KPIs: total leads, pipeline stage distribution, donation statistics, follow-up performance, and collection summaries. Filter by branch, employee, period, donation type, or pipeline stage.
2. **Lead management** — View, create, edit, import, export, and delete any lead across all branches. Assign or reassign leads to any employee.
3. **Follow-up & transitions** — Open the transition popup on any lead (from the lead list, lead detail, Kanban board, or daily tasks) to record a call outcome, move the lead through pipeline stages, and log donations. The popup enforces the same business rules for every user:
   - **No Answer / Busy** — requires a callback date.
   - **Not Interested** — requires a disinterest reason; automatically clears the callback date.
   - **Donated** — records a donation (instant or collection) and, for first-time donors, creates the donation record and advances the lead to the donor stage.
   - **Follow-up Later** — sets the next follow-up date without changing the pipeline stage.
4. **Kanban board** — Drag leads between pipeline columns. Dropping a card opens the transition popup pre-set to the target stage. Click the stage transition buttons for the same workflow without drag-and-drop.
5. **Campaigns** — Create campaigns, assign leads and employees, distribute leads, and view campaign performance reports.
6. **Collections** — View all collection cases across branches, assign collectors, reschedule visits, confirm receipt of donations, or cancel collection requests.
7. **Calendar & events** — Create, edit, reschedule, and delete events. Link follow-ups to calendar entries.
8. **VoIP monitoring** — Access the live PBX panel, review call logs and statistics per extension, listen to call recordings.
9. **Reports** — View VoIP team reports, lead reports, task reports, and employee performance dashboards.
10. **Settings** — Full access to:
    - **Users** — Create, edit, activate/deactivate users, reset passwords.
    - **Groups & permissions** — Create groups, assign granular permissions to each group.
    - **Branches** — Create, edit, activate/deactivate, and delete branches.
    - **Pipeline stages** — Add, reorder, and configure pipeline stages; manage stage-specific fields and presets.
    - **Lead fields** — Add custom lead fields, reorder them, toggle visibility.
    - **Option sets** — Manage dropdown option sets (donation types, purposes, sources, etc.).
    - **Notification rules** — Create and configure automated notification triggers.
    - **VoIP settings** — Pair/disconnect the PBX system, assign extensions to users.
    - **Instant donation methods** — Configure available payment methods.

### Branch switching

The system administrator can switch between branches at any time using the branch selector. The selected branch filters all data views (leads, collections, dashboard) without restricting access.

---

## 2. Sales Agent / Follow-up Agent (موظف المبيعات / المتابعات)

**Typical permissions:** `leads.view`, `leads.followups.view`, `leads.followups.create`, `tasks.view`
**Scope:** Own leads only (assigned to them or created by them), filtered to their branch.

### Daily workflow

1. **Daily Tasks** — The primary work surface. Open the daily task view to see all leads requiring action today. Three task scopes:
   - **Today** — leads with a follow-up date of today.
   - **Overdue** — leads with a past follow-up date that haven't been contacted.
   - **Upcoming** — leads with future follow-up dates.
   - **No Date** — leads that have never been assigned a follow-up date.
   - **Completed** — leads whose last follow-up was today.

   Switch between **card view** and **table view**. Filter by search text, status, stage, or assigned employee. Sort by follow-up date or creation date.

2. **Log a follow-up** — Click any lead's transition button (phone icon, follow-up button, or donation button) to open the transition popup. Record:
   - Communication type (call, WhatsApp, email, meeting, other).
   - Call outcome / result notes.
   - New pipeline status.
   - Next follow-up date (if applicable).
   - Stage-specific fields (e.g., viewing date, property details) when the target stage requires them.

3. **Record a donation** — When converting a lead to donor status, the transition popup presents the donation form:
   - For **instant donations**: select the payment method, enter the amount, donation type, cycle, and optionally upload a receipt.
   - For **collection donations**: enter the expected amount, type, cycle, collection address, and schedule date — this creates a collection case assigned to a field collector.

4. **Status queue pages** — Navigate to dedicated status pages (New, No Answer, Not Interested, Donor) to see all leads in that status grouped by follow-up urgency.

5. **Lead detail** — View full lead profile, phone numbers, follow-up history, status change timeline, and donation records. Use the transition button to log the next follow-up without leaving the profile.

6. **Reschedule** — Reschedule a lead's follow-up date directly from the daily tasks view without opening the full transition popup.

### What they cannot do

- View leads assigned to other employees (unless granted `leads.scope.group` or `leads.scope.all`).
- Edit lead profile data (requires `leads.update`).
- Delete leads, import/export, create campaigns, access settings, or manage collections.

---

## 3. Sales Supervisor / Team Lead (مشرف المبيعات)

**Typical permissions:** All sales agent permissions plus `leads.scope.group`, `leads.create`, `leads.update`, `leads.assign`, `leads.export`, `campaigns.view`, `reports.view`
**Scope:** Leads belonging to their group's members plus their own leads, within their branch.

### Daily workflow

1. **Monitor team performance** — Open the dashboard to view pipeline distribution, follow-up counts, and donation statistics filtered to their branch. Review which agents have overdue follow-ups.

2. **Daily Tasks with team view** — Filter the daily tasks list by employee to check each agent's workload and follow-up backlog.

3. **Lead assignment** — Assign or reassign leads between team members. When importing or creating leads, designate the responsible agent.

4. **Create leads** — Add new leads manually with full profile data (name, phone, branch, source, custom fields). The lead defaults to the "New" pipeline stage.

5. **Edit lead profiles** — Update lead contact information, branch assignment, custom fields, and notes. Profile edits never change the pipeline stage — stage transitions are only possible through the follow-up popup.

6. **Export leads** — Export filtered or selected leads to Excel/CSV for reporting or offline review.

7. **Campaign monitoring** — View active campaigns and their lead lists. Track which campaign leads have been contacted and their outcomes.

8. **Follow-up & transitions** — Same transition popup workflow as sales agents, with visibility across group members' leads.

### What they cannot do

- View leads outside their group (unless granted `leads.scope.all`).
- Delete leads (requires `leads.delete`).
- Create or manage campaigns (requires `campaigns.create`).
- Access system settings, manage users, or configure VoIP.

---

## 4. Sales Manager / Branch Manager (مدير المبيعات / مدير الفرع)

**Typical permissions:** All supervisor permissions plus `leads.scope.all`, `leads.delete`, `leads.import`, `campaigns.view`, `campaigns.create`, `campaigns.reports`, `calendar.view`, `calendar.manage`, `voip.view`, `reports.view`
**Scope:** All leads within their branch (or all branches if Super Admin).

### Daily workflow

1. **Dashboard analytics** — Full access to pipeline stage cards, lead counts, donation summaries, follow-up performance, and employee productivity metrics.

2. **Kanban board** — Visualize the entire pipeline. Drag leads between stages to trigger transitions. Filter the board by employee, source, or date range. Each column shows lead count and can be paginated.

3. **Campaign management** — Create targeted campaigns, assign employees and leads, distribute leads among agents, and track campaign performance through dedicated reports.

4. **Import leads** — Upload CSV/Excel files to bulk-create leads. Preview the import with validation, map columns, and confirm. Imported leads are assigned to specified agents and branch.

5. **Export & reporting** — Export lead data for external analysis. View VoIP call statistics per extension, team reports, and employee performance dashboards.

6. **Calendar** — Create organization events (meetings, training, deadlines), link them to leads, and manage the shared calendar.

7. **VoIP insights** — Review call logs, listen to recordings (if permitted), and monitor live PBX activity to track call volume and agent availability.

8. **Lead lifecycle management** — Full CRUD on leads plus transitions. Delete duplicate or invalid leads.

---

## 5. Field Collector (مندوب التحصيل)

**Typical permissions:** `collections.view`, `collections.collect`
**Scope:** Only collection cases assigned to them.

### Daily workflow

1. **Collection dashboard** — View assigned collection cases filtered by status:
   - **Pending** — newly created, not yet scheduled.
   - **Assigned** — assigned but no visit date set.
   - **Scheduled** — visit date confirmed.
   - **Failed** — previous collection attempt unsuccessful.

2. **View case details** — See the donor's name, phone, address, expected amount, donation type, donation cycle, and any notes from the sales agent.

3. **Reschedule** — If the donor is unavailable, reschedule the visit to a new date. Provide a reason note. Status changes to "Scheduled."

4. **Report failure** — If the collection attempt fails (donor absent, refused, incorrect address), mark the case as failed with mandatory notes explaining the situation. The case remains open for reassignment.

5. **Complete collection** — After receiving the donation:
   - Upload a photo of the donation receipt (required, image only: PNG/JPG/WebP, max 5 MB).
   - Add optional notes.
   - The system automatically:
     - Creates a donation record linked to the lead.
     - Marks the case as "Collected."
     - Sets the lead's next follow-up date based on the donation cycle (monthly → +1 month, quarterly → +3 months, etc.).
   - A donation receipt can be viewed and downloaded afterward.

### What they cannot do

- View leads or access the lead list.
- Create or edit leads.
- Log follow-ups or change pipeline stages.
- Assign cases to other collectors (requires `collections.assign`).
- Cancel cases (requires `collections.cancel`).
- Confirm receipt at the branch level (requires `collections.complete`).

---

## 6. Collection Supervisor (مشرف التحصيل)

**Typical permissions:** `collections.view`, `collections.assign`, `collections.manage`, `collections.complete`, `collections.cancel`, `collections.reports`, `collections.methods.manage`
**Scope:** All collection cases in their branch.

### Daily workflow

1. **Branch collection overview** — View all collection cases for the branch: pending, assigned, scheduled, collected, failed, and cancelled. Filter by collector, status, date range, or donor.

2. **Assign & reassign** — Assign pending cases to available field collectors. Reassign failed or unattended cases to different collectors. The system validates that the selected collector belongs to the same branch and has the `collections.collect` permission.

3. **Cancel cases** — Cancel invalid or duplicate collection requests with a reason note. Status changes to "Cancelled" and the case closes.

4. **Confirm collection** — Review and confirm completed collection cases. Verify the uploaded receipt.

5. **Manage donation methods** — Configure instant donation methods (bank transfer, mobile wallet, cash, etc.) with display order and active/inactive status.

6. **Collection reports** — View collection performance metrics: total collected, pending amounts, collector productivity, failure rates.

---

## 7. Campaign Manager (مدير الحملات)

**Typical permissions:** `leads.view`, `leads.scope.all`, `campaigns.view`, `campaigns.create`, `campaigns.reports`, `leads.followups.view`, `leads.followups.create`, `leads.assign`
**Scope:** All leads (for campaign assignment); campaign-specific operations.

### Daily workflow

1. **Create campaigns** — Define campaign name, description, start/end dates, and goals. Assign employees who will work the campaign.

2. **Assign leads** — Select leads from the lead list and assign them to the campaign. Distribute leads among campaign employees evenly or manually.

3. **Monitor progress** — View the campaign detail page showing all assigned leads, their current status, last follow-up date, and assigned employee. Track conversion rates and contact rates.

4. **Log follow-ups from campaign** — Open the transition popup directly from the campaign lead list. Record call outcomes, advance leads through the pipeline, and log donations — all attributed to the campaign.

5. **Campaign reports** — View aggregate statistics: leads contacted vs. total, conversion rate by status, donations recorded, and per-employee performance within the campaign.

6. **Edit/delete campaigns** — Update campaign details, reassign employees, or delete campaigns that are no longer active.

---

## 8. VoIP Administrator (مسؤول السنترال)

**Typical permissions:** `voip.view`, `voip.recordings`, `voip.live_panel`, `voip.settings`
**Scope:** PBX system configuration and monitoring.

### Daily workflow

1. **Live PBX panel** — Monitor real-time call activity: active calls, queued calls, agent availability, ring times, and extensions in use. Auto-refreshes for live monitoring.

2. **Call logs & statistics** — Review detailed call records per extension: call duration, direction (inbound/outbound), timestamps, and outcomes. Filter by date range and extension.

3. **Recordings** — Listen to and download recorded calls for quality assurance, training, or dispute resolution.

4. **VoIP settings** — Pair the CRM with the PBX system using API credentials. Assign VoIP extensions to CRM users. Disconnect and reconfigure the integration.

5. **Extension performance** — View per-extension analytics: total calls, average duration, missed call rate, peak hours.

6. **Lead call history** — View all PBX calls associated with a specific lead, correlated by phone number. Available from the lead detail page.

### Personal call profile

Every user with `voip.view` permission can access their own call profile (`/my/calls`) to review their personal call statistics and history.

---

## 9. Read-Only Viewer / Auditor (مراجع / مراقب)

**Typical permissions:** `dashboard.view`, `leads.view`, `leads.scope.all`, `leads.followups.view`, `reports.view`
**Scope:** Read-only access across all branches.

### Daily workflow

1. **Dashboard review** — Monitor KPIs without the ability to modify any data.
2. **Lead browsing** — View lead profiles, follow-up history, status timelines, and donation records.
3. **Reports** — Access all reporting dashboards for oversight and compliance.

### What they cannot do

- Create, edit, or delete any record.
- Log follow-ups or transitions.
- Access settings or manage users.

---

## Lead Visibility Rules

Lead visibility is controlled by three scope levels:

| Scope Permission | Leads Visible |
|---|---|
| *(none)* | Only leads assigned to or created by the user |
| `leads.scope.group` | Above + leads assigned to users in the same group(s) |
| `leads.scope.all` | All leads in the user's branch (or all branches for Super Admin) |

Branch filtering is always applied:
- **Regular users** see only leads in their assigned branch.
- **Super Admins** see all branches by default and can filter to a specific branch using the branch switcher.

---

## Unified Transition Workflow

All employees who can log follow-ups use the **same transition popup** regardless of where they trigger it:

| Surface | Trigger | Popup behavior |
|---|---|---|
| Lead list | Follow-up / donation button per row | Opens with current status pre-selected |
| Lead detail page | Follow-up / donation button | Opens with current status; donation button pre-selects "Donated" |
| Lead edit page | Transition button in header | Opens with current status pre-selected |
| Kanban board — drag | Drop card on target column | Opens with target stage pre-selected |
| Kanban board — button | Stage transition button on card | Opens with button's target stage pre-selected |
| Daily tasks — card view | Follow-up / donation / call button | Opens with current status or "Donated" pre-selected |
| Daily tasks — table view | Follow-up / donation button per row | Same as card view |
| Status queue page | Follow-up button per lead | Opens with current status pre-selected |
| Campaign detail | Follow-up button per campaign lead | Opens with current status; attributes follow-up to campaign |

The popup always presents four outcome buttons:
1. **تم التبرع (Donated)** — record a donation and advance to donor stage.
2. **لم يرد / مشغول (No Answer / Busy)** — mark as unreachable, set callback date.
3. **متابعة لاحقة (Follow-up Later)** — set next follow-up without changing stage.
4. **غير مهتم (Not Interested)** — mark as disinterested, clear follow-up date.

### Business rules enforced by the popup

- **No Answer** requires a future callback date.
- **Not Interested** requires a disinterest reason and automatically clears the callback.
- **Donated** requires donation details (amount, type, cycle, payment method or collection schedule).
- **First-time donor conversion** additionally creates the donation record and advances the pipeline stage.
- **Stage-specific fields** (configured per pipeline stage in settings) appear dynamically when the target stage requires them.
- **Profile edits** (name, phone, address, etc.) on the lead edit page **never** change the pipeline stage. Stage transitions are only possible through the transition popup.

---

## Permission Reference

| Module | Permission | Description |
|---|---|---|
| Dashboard | `dashboard.view` | View the analytics dashboard |
| Settings | `settings.access` | Access the settings area |
| Notifications | `notifications.manage` | Manage notification rules |
| Leads | `leads.view` | View leads |
| | `leads.scope.all` | See all leads (not just own) |
| | `leads.scope.group` | See leads of group members |
| | `leads.assign` | Assign leads to other users |
| | `leads.create` | Create new leads |
| | `leads.update` | Edit lead profiles |
| | `leads.delete` | Delete leads |
| | `leads.import` | Import leads from file |
| | `leads.export` | Export leads to file |
| | `leads.followups.view` | View follow-up history |
| | `leads.followups.create` | Log follow-ups and transitions |
| Tasks | `tasks.view` | Access daily tasks and status queues |
| Campaigns | `campaigns.view` | View campaigns |
| | `campaigns.create` | Create and manage campaigns |
| | `campaigns.reports` | View campaign reports |
| Reports | `reports.view` | Access reporting dashboards |
| Users | `users.view` | View user list |
| | `users.create` | Create new users |
| | `users.update` | Edit users and group membership |
| | `users.activate` | Activate / deactivate users |
| | `users.reset_password` | Reset user passwords |
| Groups | `groups.view` | View groups |
| | `groups.create` | Create groups |
| | `groups.update` | Edit groups |
| | `groups.delete` | Delete groups |
| | `groups.assign_permissions` | Assign permissions to groups |
| VoIP | `voip.view` | View call logs and statistics |
| | `voip.recordings` | Listen to call recordings |
| | `voip.live_panel` | Access live PBX monitoring |
| | `voip.settings` | Configure VoIP integration |
| Calendar | `calendar.view` | View calendar and events |
| | `calendar.manage` | Create and manage events |
| Collections | `collections.view` | View collection cases |
| | `collections.collect` | Execute assigned collections |
| | `collections.assign` | Assign collectors to cases |
| | `collections.manage` | Manage branch collections |
| | `collections.complete` | Confirm collection receipt |
| | `collections.cancel` | Cancel collection requests |
| | `collections.reports` | View collection reports |
| | `collections.methods.manage` | Manage instant donation methods |
| Branches | `branches.view` | View branches |
| | `branches.create` | Create branches |
| | `branches.update` | Edit branches |
| | `branches.delete` | Delete branches |
