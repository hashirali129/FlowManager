# User Journeys - FlowManager

Complete user journey flows for all system roles in FlowManager.

---

## Table of Contents

1. [Admin Journey](#1-admin-journey)
2. [HR Journey](#2-hr-journey)
3. [Manager Journey](#3-manager-journey)
4. [Employee Journey](#4-employee-journey)

---

## 1. Admin Journey

### Overview
Admins have full system access and are responsible for configuration, user management, and system monitoring.

### Admin Flowchart

```mermaid
flowchart TD
    Start([Admin Login]) --> Auth{Authentication}
    Auth -->|Success| Dashboard[Admin Dashboard]
    Auth -->|Failure| Start
    
    Dashboard --> MainChoice{Select Task}
    
    %% Request Type Management
    MainChoice -->|Configure Request Types| RT[Request Types]
    RT --> RTAction{Action}
    RTAction -->|Create New| RT1[Enter Type Details]
    RTAction -->|Edit Existing| RT2[Modify Type]
    RTAction -->|View All| RT3[List Types]
    
    RT1 --> RT1A[Name & Description]
    RT1A --> RT1B[Define Form Schema]
    RT1B --> RT1C{Validate Schema}
    RT1C -->|Invalid| RT1B
    RT1C -->|Valid| RT1D[Save Type]
    RT1D --> RTDone[Type Created]
    
    RT2 --> RT2A[Update Details]
    RT2A --> RTDone
    RT3 --> RTDone
    RTDone --> Dashboard
    
    %% Workflow Management
    MainChoice -->|Configure Workflows| WF[Workflow Management]
    WF --> WFAction{Action}
    WFAction -->|Create| WF1[New Workflow]
    WFAction -->|Edit| WF2[Modify Workflow]
    WFAction -->|Delete| WF3[Remove Workflow]
    
    WF1 --> WF1A[Select Request Type]
    WF1A --> WF1B[Add Workflow Step]
    WF1B --> WF1C[Set Step Order]
    WF1C --> WF1D[Assign Role]
    WF1D --> WF1E{Add More Steps?}
    WF1E -->|Yes| WF1B
    WF1E -->|No| WF1F[Save Workflow]
    WF1F --> WFDone[Workflow Ready]
    
    WF2 --> WF2A[Reorder/Edit Steps]
    WF2A --> WFDone
    
    WF3 --> WF3A{Active Requests?}
    WF3A -->|Yes| WF3B[Cannot Delete]
    WF3A -->|No| WF3C[Delete]
    WF3B --> WFDone
    WF3C --> WFDone
    WFDone --> Dashboard
    
    %% Team Management
    MainChoice -->|Manage Teams| TM[Team Management]
    TM --> TMAction{Action}
    TMAction -->|Create| TM1[New Team]
    TMAction -->|Edit| TM2[Update Team]
    TMAction -->|Assign Members| TM3[Member Assignment]
    
    TM1 --> TM1A[Team Name]
    TM1A --> TM1B[Select Manager]
    TM1B --> TM1C[Save Team]
    TM1C --> TMDone[Team Created]
    
    TM2 --> TM2A[Edit Details]
    TM2A --> TMDone
    
    TM3 --> TM3A[Select Users]
    TM3A --> TM3B[Assign to Team]
    TM3B --> TMDone
    TMDone --> Dashboard
    
    %% User Management
    MainChoice -->|Manage Users| UM[User Management]
    UM --> UMAction{Action}
    UMAction -->|Create| UM1[New User]
    UMAction -->|Edit| UM2[Edit User]
    UMAction -->|Assign Roles| UM3[Role Assignment]
    UMAction -->|Set Manager| UM4[Manager Assignment]
    
    UM1 --> UM1A[User Details]
    UM1A --> UM1B[Assign Team]
    UM1B --> UM1C[Assign Direct Manager]
    UM1C --> UM1D[Set Roles]
    UM1D --> UM1E[Save User]
    UM1E --> UMDone[User Created]
    
    UM2 --> UM2A[Update Info]
    UM2A --> UMDone
    
    UM3 --> UM3A[Select User]
    UM3A --> UM3B[Modify Roles]
    UM3B --> UMDone
    
    UM4 --> UM4A[Select Employee]
    UM4A --> UM4B[Choose Manager]
    UM4B --> UMDone
    UMDone --> Dashboard
    
    %% Monitoring
    MainChoice -->|Monitor System| MON[Monitoring]
    MON --> MONChoice{Dashboard}
    MONChoice -->|Horizon| HORZ[Queue Dashboard]
    MONChoice -->|Telescope| TELE[Application Debug]
    
    HORZ --> HORZ1[View Jobs]
    HORZ1 --> HORZ2{Failed Jobs?}
    HORZ2 -->|Yes| HORZ3[Retry/Delete]
    HORZ2 -->|No| HONDone[All Good]
    HORZ3 --> HONDone
    
    TELE --> TELE1[Check Requests]
    TELE1 --> TELE2[Review Queries]
    TELE2 --> TELE3[View Exceptions]
    TELE3 --> HONDone
    
    HONDone --> Dashboard
    
    Dashboard -->|Logout| End([Session End])
    
    style Start fill:#e1f5e1
    style Dashboard fill:#e3f2fd
    style End fill:#ffcdd2
```

### Admin Capabilities

| Task | Description | Outcome |
|------|-------------|---------|
| **Request Type Creation** | Define new request categories with custom form schemas | New request types available to users |
| **Workflow Configuration** | Build multi-step approval processes | Automated routing of requests |
| **Team Management** | Organize users into teams with managers | Hierarchical structure |
| **User Administration** | Create, edit, and manage user accounts | User access control |
| **Role Assignment** | Grant permissions via role assignment | Authorization control |
| **System Monitoring** | Track application health and job queues | Proactive issue resolution |

---

## 2. HR Journey

### Overview
HR personnel handle human resources requests, manage team structures, and serve as fallback approvers.

### HR Flowchart

```mermaid
flowchart TD
    Start([HR Login]) --> Auth{Authenticate}
    Auth -->|Success| Dashboard[HR Dashboard]
    Auth -->|Failure| Start
    
    Dashboard --> Choice{Select Action}
    
    %% View Pending Approvals
    Choice -->|Review Approvals| Approvals[Pending Approvals]
    Approvals --> AppList[Load HR Approvals]
    AppList --> AppSelect{Select Request}
    
    AppSelect --> AppView[View Request Details]
    AppView --> AppCheck[Review Information]
    AppCheck --> AppDocs{Documents?}
    AppDocs -->|Yes| AppDownload[Download & Review]
    AppDocs -->|No| AppDecision
    AppDownload --> AppDecision{Decision}
    
    AppDecision -->|Approve| AppApprove[Submit Approval]
    AppDecision -->|Reject| AppReject[Submit Rejection]
    AppDecision -->|Need Info| AppComment[Add Comment & Hold]
    
    AppApprove --> AppApprove1[Add Comments]
    AppApprove1 --> AppApprove2[Confirm Approval]
    AppApprove2 --> AppNotify[System Notifies Next Step]
    
    AppReject --> AppReject1[Enter Rejection Reason]
    AppReject1 --> AppReject2[Confirm Rejection]
    AppReject2 --> AppNotifyReject[Notify Requester]
    
    AppComment --> AppDone[Return to List]
    AppNotify --> AppDone
    AppNotifyReject --> AppDone
    AppDone --> Approvals
    
    %% Team Management
    Choice -->|Manage Teams| Teams[Team Management]
    Teams --> TeamAction{Action}
    TeamAction -->|View Teams| TeamList[List All Teams]
    TeamAction -->|Edit Team| TeamEdit[Modify Team]
    TeamAction -->|Assign Members| TeamAssign[Member Assignment]
    
    TeamEdit --> TeamEdit1[Update Details]
    TeamEdit1 --> TeamDone[Changes Saved]
    
    TeamAssign --> TeamAssign1[Select Employees]
    TeamAssign1 --> TeamAssign2[Move to Team]
    TeamAssign2 --> TeamDone
    
    TeamList --> TeamDone
    TeamDone --> Dashboard
    
    %% View All Requests
    Choice -->|View All Requests| AllReq[Request Overview]
    AllReq --> AllReqFilter{Filter By}
    AllReqFilter -->|Status| AllReq1[Filter Status]
    AllReqFilter -->|Team| AllReq2[Filter Team]
    AllReqFilter -->|Type| AllReq3[Filter Type]
    
    AllReq1 --> AllReqView[View Filtered]
    AllReq2 --> AllReqView
    AllReq3 --> AllReqView
    AllReqView --> AllReqDetail{View Details?}
    AllReqDetail -->|Yes| AllReqOpen[Open Request]
    AllReqDetail -->|No| Dashboard
    AllReqOpen --> Dashboard
    
    %% Reports
    Choice -->|Generate Reports| Report[Reporting]
    Report --> RepType{Report Type}
    RepType -->|Team Performance| Rep1[Team Metrics]
    RepType -->|Request Volume| Rep2[Volume Stats]
    RepType -->|Approval Time| Rep3[Time Analytics]
    
    Rep1 --> RepExport{Export?}
    Rep2 --> RepExport
    Rep3 --> RepExport
    RepExport -->|Yes| RepFile[Download CSV/PDF]
    RepExport -->|No| RepDone[View Only]
    RepFile --> RepDone
    RepDone --> Dashboard
    
    Dashboard -->|Logout| End([Session End])
    
    style Start fill:#e1f5e1
    style Dashboard fill:#fff9c4
    style End fill:#ffcdd2
```

### HR Responsibilities

| Responsibility | Actions | Impact |
|----------------|---------|--------|
| **Approval Processing** | Review and approve/reject HR-level requests | Request progression |
| **Team Organization** | Manage team structures and assignments | Organizational hierarchy |
| **Fallback Approver** | Approve requests when managers are unavailable | Workflow continuity |
| **Reporting** | Generate analytics on team and request metrics | Data-driven decisions |
| **User Support** | Assist employees with request-related issues | User satisfaction |

---

## 3. Manager Journey

### Overview
Managers approve requests from their team members and manage direct reports.

### Manager Flowchart

```mermaid
flowchart TD
    Start([Manager Login]) --> Auth{Authenticate}
    Auth -->|Success| Dashboard[Manager Dashboard]
    Auth -->|Failure| Start
    
    Dashboard --> Choice{Select Action}
    
    %% Pending Approvals
    Choice -->|My Approvals| Approvals[Pending Approvals]
    Approvals --> LoadApp[Load Manager Queue]
    LoadApp --> AppCount{Any Pending?}
    AppCount -->|No| AppEmpty[No Approvals]
    AppCount -->|Yes| AppList[Display List]
    
    AppEmpty --> Dashboard
    AppList --> AppSelect[Select Request]
    AppSelect --> AppDetails[View Full Details]
    
    AppDetails --> AppReview[Review Request]
    AppReview --> AppPayload{Check Payload}
    AppPayload --> AppDocs{Has Documents?}
    AppDocs -->|Yes| AppViewDocs[Download Documents]
    AppDocs -->|No| AppDecide
    AppViewDocs --> AppDecide{Make Decision}
    
    AppDecide -->|Approve| AppApp[Approve Request]
    AppDecide -->|Reject| AppRej[Reject Request]
    AppDecide -->|Defer| AppDefer[Add Comment & Return]
    
    AppApp --> AppAppCom[Enter Comments]
    AppAppCom --> AppAppConf[Confirm Approval]
    AppAppConf --> AppAppSave[(Save to DB)]
    AppAppSave --> AppAppEvent[Fire RequestApproved Event]
    AppAppEvent --> AppAppNext{Next Step Exists?}
    AppAppNext -->|Yes| AppAppRoute[Route to Next Approver]
    AppAppNext -->|No| AppAppComplete[Mark Request Approved]
    AppAppRoute --> AppAppNotify[Notify Next Approver]
    AppAppComplete --> AppAppNotifyReq[Notify Requester]
    AppAppNotify --> AppDone[Return to List]
    AppAppNotifyReq --> AppDone
    
    AppRej --> AppRejReason[Enter Rejection Reason]
    AppRejReason --> AppRejConf[Confirm Rejection]
    AppRejConf --> AppRejSave[(Update DB)]
    AppRejSave --> AppRejEvent[Fire RequestRejected Event]
    AppRejEvent --> AppRejNotify[Notify Requester]
    AppRejNotify --> AppDone
    
    AppDefer --> AppDeferSave[Save Comment]
    AppDeferSave --> AppDone
    AppDone --> Approvals
    
    %% Team Requests
    Choice -->|Team Requests| TeamReq[Team Overview]
    TeamReq --> TeamFilter{Filter}
    TeamFilter -->|All| TeamAll[All Team Requests]
    TeamFilter -->|Pending| TeamPend[Pending Only]
    TeamFilter -->|Approved| TeamApp[Approved Only]
    TeamFilter -->|Rejected| TeamRej[Rejected Only]
    
    TeamAll --> TeamView[View List]
    TeamPend --> TeamView
    TeamApp --> TeamView
    TeamRej --> TeamView
    TeamView --> TeamDetail{View Details?}
    TeamDetail -->|Yes| TeamOpen[Open Request]
    TeamDetail -->|No| Dashboard
    TeamOpen --> Dashboard
    
    %% My Team
    Choice -->|My Team Members| Team[Team Management]
    Team --> TeamList[List Direct Reports]
    TeamList --> TeamMember{Select Member}
    TeamMember --> TeamMemberView[View Member Info]
    TeamMemberView --> TeamMemberReq[View Member Requests]
    TeamMemberReq --> Dashboard
    
    Dashboard -->|Logout| End([Session End])
    
    style Start fill:#e1f5e1
    style Dashboard fill:#e1bee7
    style End fill:#ffcdd2
```

### Manager Responsibilities

| Responsibility | Actions | Impact |
|----------------|---------|--------|
| **Request Approval** | Review and approve/reject team requests | Workflow progression |
| **Team Oversight** | Monitor team member requests | Team performance tracking |
| **Direct Report Management** | Manage direct reports hierarchy | Organizational structure |
| **Timely Processing** | Ensure quick turnaround on approvals | Employee satisfaction |

---

## 4. Employee Journey

### Overview
Employees submit requests and track their progress through the approval workflow.

### Employee Flowchart

```mermaid
flowchart TD
    Start([Employee Login]) --> Auth{Authenticate}
    Auth -->|Success| Dashboard[Employee Dashboard]
    Auth -->|Failure| Start
    
    Dashboard --> Choice{Select Action}
    
    %% Create New Request
    Choice -->|New Request| NewReq[Create Request]
    NewReq --> ReqType[Select Request Type]
    ReqType --> ReqForm[Load Dynamic Form]
    ReqForm --> ReqFill[Fill Form Fields]
    
    ReqFill --> ReqValidate{Validation}
    ReqValidate -->|Invalid| ReqError[Show Errors]
    ReqError --> ReqFill
    ReqValidate -->|Valid| ReqDocs{Add Documents?}
    
    ReqDocs -->|Yes| ReqUpload[Upload Files]
    ReqDocs -->|No| ReqSubmit
    ReqUpload --> ReqUploadLocal[(Store Locally)]
    ReqUploadLocal --> ReqSubmit[Submit Request]
    
    ReqSubmit --> ReqCreate[(Create Request DB)]
    ReqCreate --> ReqWorkflow[Initiate Workflow]
    ReqWorkflow --> ReqApproval[(Create First Approval)]
    ReqApproval --> ReqEvent[Fire RequestCreated Event]
    
    ReqEvent -.->|Async| ReqEmail[Send Email to Approver]
    ReqEvent -.->|Async| ReqS3[Upload Docs to S3]
    
    ReqSubmit --> ReqSuccess[Request Submitted]
    ReqSuccess --> ReqConfirm[Show Confirmation]
    ReqConfirm --> Dashboard
    
    %% View My Requests
    Choice -->|My Requests| MyReq[Request List]
    MyReq --> MyReqLoad[Load User Requests]
    MyReqLoad --> MyReqFilter{Filter}
    MyReqFilter -->|All| MyReqAll[All Requests]
    MyReqFilter -->|Pending| MyReqPend[Pending]
    MyReqFilter -->|Approved| MyReqApp[Approved]
    MyReqFilter -->|Rejected| MyReqRej[Rejected]
    
    MyReqAll --> MyReqList[Display List]
    MyReqPend --> MyReqList
    MyReqApp --> MyReqList
    MyReqRej --> MyReqList
    
    MyReqList --> MyReqSelect{Select Request}
    MyReqSelect --> MyReqView[View Details]
    MyReqView --> MyReqInfo[See Request Info]
    MyReqInfo --> MyReqStatus[Check Approval Status]
    MyReqStatus --> MyReqApprovals[View Approval History]
    MyReqApprovals --> MyReqDocs{View Documents?}
    MyReqDocs -->|Yes| MyReqDownload[Download Files]
    MyReqDocs -->|No| MyReqBack
    MyReqDownload --> MyReqBack[Back to List]
    MyReqBack --> MyReq
    
    %% Track Status
    Choice -->|Track Request| Track[Request Tracking]
    Track --> TrackID[Enter Request ID]
    TrackID --> TrackLoad[Load Request]
    TrackLoad --> TrackExists{Found?}
    TrackExists -->|No| TrackError[Not Found]
    TrackExists -->|Yes| TrackTimeline[View Timeline]
    
    TrackError --> Dashboard
    TrackTimeline --> TrackSteps[Approval Steps]
    TrackSteps --> TrackCurrent[Current Step]
    TrackCurrent --> TrackApprover[Current Approver]
    TrackApprover --> TrackHistory[Approval History]
    TrackHistory --> Dashboard
    
    %% Notifications
    Choice -->|Notifications| Notif[View Notifications]
    Notif --> NotifList[Load Notifications]
    NotifList --> NotifType{Type}
    NotifType -->|Approved| NotifApp[Approval Notice]
    NotifType -->|Rejected| NotifRej[Rejection Notice]
    NotifType -->|Comment| NotifCom[Comment Added]
    
    NotifApp --> NotifAction[View Request]
    NotifRej --> NotifAction
    NotifCom --> NotifAction
    NotifAction --> Dashboard
    
    %% Profile
    Choice -->|My Profile| Profile[User Profile]
    Profile --> ProfileView[View Details]
    ProfileView --> ProfileInfo[Personal Info]
    ProfileInfo --> ProfileTeam[Team Info]
    ProfileTeam --> ProfileManager[Manager Info]
    ProfileManager --> Dashboard
    
    Dashboard -->|Logout| End([Session End])
    
    style Start fill:#e1f5e1
    style Dashboard fill:#c8e6c9
    style End fill:#ffcdd2
    style ReqEmail fill:#fff59d
    style ReqS3 fill:#fff59d
```

### Employee Capabilities

| Capability | Actions | Outcome |
|-----------|---------|---------|
| **Request Submission** | Create and submit requests via dynamic forms | Request enters workflow |
| **Document Upload** | Attach supporting documents | Evidence for approvers |
| **Request Tracking** | Monitor approval progress in real-time | Transparency |
| **Notification Receipt** | Receive updates on request status | Timely information |
| **History Review** | View past requests and decisions | Record keeping |

---

## User Journey Comparison

| Feature | Admin | HR | Manager | Employee |
|---------|-------|----|---------| |
| **Create Requests** | ❌ | ✅ | ✅ | ✅ |
| **Approve Requests** | ❌ | ✅ | ✅ | ❌ |
| **Configure Workflows** | ✅ | ❌ | ❌ | ❌ |
| **Manage Users** | ✅ | ✅ (Limited) | ❌ | ❌ |
| **Manage Teams** | ✅ | ✅ | ✅ (View) | ❌ |
| **System Monitoring** | ✅ | ❌ | ❌ | ❌ |
| **View All Requests** | ✅ | ✅ | ✅ (Team) | ✅ (Own) |
| **Generate Reports** | ✅ | ✅ | ✅ (Team) | ❌ |

---

## Key Interaction Patterns

### Request Lifecycle Interaction

```mermaid
sequenceDiagram
    participant E as Employee
    participant S as System
    participant M as Manager
    participant H as HR
    participant F as Finance
    
    E->>S: Submit Request
    S->>S: Create Request
    S->>S: Initiate Workflow
    S-->>E: Confirmation
    S-->>M: Email Notification
    
    M->>S: View Pending
    M->>S: Approve Request
    S->>S: Update Status
    S-->>H: Email Notification
    
    H->>S: Approve Request
    S->>S: Update Status
    S-->>F: Email Notification
    
    F->>S: Approve Request
    S->>S: Mark Complete
    S-->>E: Approval Notification
```

### Hierarchical Approver Selection

```mermaid
flowchart TD
    Start[New Request] --> CheckStep{Check Required Role}
    CheckStep -->|Manager| HasManager{User Has Manager?}
    CheckStep -->|HR| HR[Assign to HR Role]
    CheckStep -->|Finance| Finance[Assign to Finance Role]
    
    HasManager -->|Yes| DirectMgr[Assign to Direct Manager]
    HasManager -->|No| TeamMgr{Has Team Manager?}
    
    TeamMgr -->|Yes| AssignTeamMgr[Assign to Team Manager]
    TeamMgr -->|No| FallbackHR[Fallback to HR]
    
    DirectMgr --> Done[Approval Created]
    AssignTeamMgr --> Done
    FallbackHR --> Done
    HR --> Done
    Finance --> Done
```

---

## Conclusion

Each role in FlowManager has a distinct journey optimized for their responsibilities:

- **Admins** focus on system configuration and oversight
- **HR** handles organizational management and serves as fallback approvers
- **Managers** process team requests efficiently
- **Employees** have a streamlined submission and tracking experience

The workflows are designed to ensure:
- ✅ **Clear Separation of Concerns**
- ✅ **Efficient Approval Routing**
- ✅ **Transparent Progress Tracking**
- ✅ **Automated Notifications**
