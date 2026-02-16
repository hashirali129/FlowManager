# Feature Flows - FlowManager

Detailed flows for core application features including request management, approval workflows, and document handling.

---

## Table of Contents

1. [Request Creation & Management](#1-request-creation--management)
2. [Approval Workflow Processing](#2-approval-workflow-processing)
3. [Document Upload & Storage](#3-document-upload--storage)
4. [Email Notification System](#4-email-notification-system)
5. [Workflow Configuration](#5-workflow-configuration)

---

## 1. Request Creation & Management

### Request Submission Flow

```mermaid
flowchart TD
    Start([User Initiates Request]) --> LoadTypes[GET /api/request-types]
    LoadTypes --> TypesResp[Display Request Types]
    TypesResp --> SelectType[User Selects Type]
    
    SelectType --> LoadSchema[Fetch form_schema]
    LoadSchema --> RenderForm[Render Dynamic Form]
    
    RenderForm --> UserInput[User Fills Form]
    UserInput --> ClientValidate{Client Validation}
    ClientValidate -->|Invalid| ShowErrors[Display Errors]
    ShowErrors --> UserInput
    ClientValidate -->|Valid| AddDocs{Add Documents?}
    
    AddDocs -->|Yes| SelectFiles[File Input]
    AddDocs -->|No| Submit
    SelectFiles --> ValidateFiles{Validate Files}
    ValidateFiles -->|Invalid| FileErrors[Show File Errors]
    FileErrors --> SelectFiles
    ValidateFiles -->|Valid| Submit[POST /api/requests]
    
    Submit --> ServerValidate{Server Validation}
    ServerValidate -->|Invalid| ValidationErrors[422 Response]
    ValidationErrors --> UserInput
    
    ServerValidate -->|Valid| CreateRequest[RequestController store]
    CreateRequest --> ServiceCall[RequestService createRequest]
    
    ServiceCall --> WorkflowInit[WorkflowEngine initiateRequest]
    
    WorkflowInit --> CreateRecord[(Create Request in DB)]
    CreateRecord --> DetermineStep[Get First Workflow Step]
    DetermineStep --> FindApprover[Determine Approver]
    
    FindApprover --> ApproverLogic{Required Role}
    ApproverLogic -->|Manager| CheckManager{Has Manager?}
    ApproverLogic -->|HR| AssignHR[Assign HR]
    ApproverLogic -->|Finance| AssignFinance[Assign Finance]
    
    CheckManager -->|Yes| SetManager[approver_id = manager_id]
    CheckManager -->|No| CheckTeam{Has Team Manager?}
    CheckTeam -->|Yes| SetTeamMgr[approver_id = team.manager_id]
    CheckTeam -->|No| FallbackHR[approver_id = HR user]
    
    SetManager --> CreateApproval[(Create RequestApproval)]
    SetTeamMgr --> CreateApproval
    FallbackHR --> CreateApproval
    AssignHR --> CreateApproval
    AssignFinance --> CreateApproval
    
    CreateApproval --> HandleDocs{Documents Attached?}
    HandleDocs -->|Yes| StoreTmp[Store Locally: temp-uploads/]
    HandleDocs -->|No| FireEvent
    StoreTmp --> FireDocEvent[Fire DocumentsUploaded]
    FireDocEvent --> FireEvent[Fire RequestCreated]
    
    FireEvent -.->|Async| QueueEmail[Queue: SendRequestCreatedNotification]
    FireEvent -.->|Async| QueueS3[Queue: UploadDocumentsToS3]
    
    FireEvent --> Success[201 Created Response]
    Success --> End([Request Submitted])
    
    QueueEmail --> EmailJob[Build Email]
    EmailJob --> SendEmail[Send to Approver]
    
    QueueS3 --> ReadLocal[Read from temp-uploads/]
    ReadLocal --> StreamS3[Stream to S3]
    StreamS3 --> CreateDocRecord[(INSERT into request_documents)]
    CreateDocRecord --> DeleteLocal[Delete Local Copy]
    
    style CreateRecord fill:#4caf50
    style CreateApproval fill:#4caf50
    style Success fill:#81c784
    style QueueEmail fill:#fff59d
    style QueueS3 fill:#fff59d
```

### Request Viewing Flow

```mermaid
flowchart TD
    Start([User Views Requests]) --> Endpoint{Endpoint}
    
    Endpoint -->|My Requests| GetMine[GET /api/requests]
    Endpoint -->|Specific| GetOne[GET /api/requests/:id]
    
    GetMine --> AuthCheck{Authorized?}
    GetOne --> PolicyCheck{Policy Check}
    
    AuthCheck -->|Yes| LoadMine[(SELECT * FROM requests WHERE user_id)]
    AuthCheck -->|No| Unauthorized[401 Unauthorized]
    
    PolicyCheck -->|Owns Request| LoadOne[(SELECT with relationships)]
    PolicyCheck -->|No| Forbidden[403 Forbidden]
    
    LoadMine --> EagerLoad[Eager Load Relations]
    LoadOne --> EagerLoad
    
    EagerLoad --> LoadType[requestType]
    LoadType --> LoadApprovals[approvals.step.role]
    LoadApprovals --> LoadApprover[approvals.approver]
    LoadApprover --> LoadDocs[documents]
    
    LoadDocs --> Transform[RequestResource::collection]
    Transform --> JSONResp[200 OK with JSON]
    JSONResp --> End([Display to User])
    
    Unauthorized --> End
    Forbidden --> End
    
    style LoadMine fill:#4caf50
    style LoadOne fill:#4caf50
    style Transform fill:#2196f3
```

---

## 2. Approval Workflow Processing

### Approval Decision Flow

```mermaid
flowchart TD
    Start([Approver Makes Decision]) --> Submit[POST /api/requests/:id/action]
    Submit --> Validate{Validate Input}
    Validate -->|Missing action| Error422[422 Validation Error]
    Validate -->|Valid| LoadReq[(Load Request)]
    
    LoadReq --> CheckPending{Status = pending?}
    CheckPending -->|No| Error400[400 Already Processed]
    CheckPending -->|Yes| LoadApproval[(Load Current Approval)]
    
    LoadApproval --> VerifyApprover{User Can Approve?}
    VerifyApprover -->|No| Error403[403 Forbidden]
    VerifyApprover -->|Yes| BeginTx[BEGIN TRANSACTION]
    
    BeginTx --> ActionType{Action}
    
    ActionType -->|approve| UpdateApproval[(UPDATE request_approvals SET status='approved')]
    ActionType -->|reject| UpdateReject[(UPDATE request_approvals SET status='rejected')]
    
    UpdateApproval --> SetApprover[(SET approver_id = current_user)]
    SetApprover --> SaveComments[(Save comments and approved_at)]
    SaveComments --> CheckNext{Has Next Step?}
    
    CheckNext -->|Yes| GetNextStep[(SELECT next workflow_step)]
    CheckNext -->|No| MarkComplete[(UPDATE requests SET status='approved')]
    
    GetNextStep --> FindNextApprover[Determine Next Approver]
    FindNextApprover --> CreateNextApproval[(CREATE new request_approval)]
    CreateNextApproval --> UpdateCurrent[(UPDATE requests.current_step_order)]
    UpdateCurrent --> CommitApprove[COMMIT]
    
    MarkComplete --> CommitComplete[COMMIT]
    
    UpdateReject --> RejectRequest[(UPDATE requests SET status='rejected')]
    RejectRequest --> CommitReject[COMMIT]
    
    CommitApprove --> FireApproved[Fire RequestApproved Event]
    CommitComplete --> FireApproved
    CommitReject --> FireRejected[Fire RequestRejected Event]
    
    FireApproved -.->|Async| NotifyNext[Email Next Approver]
    FireApproved -.->|Async| NotifyRequester[Email Requester: Approved]
    FireRejected -.->|Async| NotifyRejection[Email Requester: Rejected]
    
    FireApproved --> Response[200 OK]
    FireRejected --> Response
    
    Response --> End([Decision Processed])
    
    Error422 --> End
    Error400 --> End
    Error403 --> End
    
    style BeginTx fill:#ff9800
    style CommitApprove fill:#4caf50
    style CommitComplete fill:#4caf50
    style CommitReject fill:#f44336
    style NotifyNext fill:#fff59d
    style NotifyRequester fill:#fff59d
    style NotifyRejection fill:#fff59d
```

### Multi-Step Workflow Example

```mermaid
stateDiagram-v2
    [*] --> Step1_Pending: Request Created
    
    Step1_Pending --> Step1_Approved: Manager Approves
    Step1_Pending --> Rejected: Manager Rejects
    
    Step1_Approved --> Step2_Pending: Route to HR
    
    Step2_Pending --> Step2_Approved: HR Approves
    Step2_Pending --> Rejected: HR Rejects
    
    Step2_Approved --> Step3_Pending: Route to Finance
    
    Step3_Pending --> Approved: Finance Approves
    Step3_Pending --> Rejected: Finance Rejects
    
    Approved --> [*]: Workflow Complete
    Rejected --> [*]: Workflow Terminated
    
    note right of Step1_Pending
        Status: pending
        Approver: Manager
    end note
    
    note right of Step2_Pending
        Status: pending
        Approver: HR
    end note
    
    note right of Step3_Pending
        Status: pending
        Approver: Finance
    end note
```

---

## 3. Document Upload & Storage

### Two-Phase Upload Pattern

```mermaid
sequenceDiagram
    autonumber
    participant User
    participant Controller
    participant Service
    participant LocalDisk as Local Storage
    participant Event as Event Bus
    participant Queue as Redis Queue
    participant Listener as S3 Listener
    participant S3 as AWS S3
    participant DB as Database
    
    User->>Controller: POST /api/requests (with files)
    Controller->>Controller: Validate multipart/form-data
    
    Controller->>Service: RequestService::createRequest()
    Service->>Service: handleFileUploads()
    
    loop For Each File
        Service->>LocalDisk: store('temp-uploads/')
        LocalDisk-->>Service: temp_path
        Service->>Service: Build file metadata
    end
    
    Service->>Event: Fire DocumentsUploaded(request, files)
    Event-->>Service: Event Dispatched
    
    Service-->>Controller: Request Created
    Controller-->>User: 201 Created (Instant Response)
    
    Note over Event,Listener: Background Processing
    
    Event->>Queue: Push to Redis Queue
    Queue->>Listener: UploadDocumentsToS3::handle()
    
    loop For Each File Metadata
        Listener->>LocalDisk: readStream(tmp_path)
        LocalDisk-->>Listener: file_stream
        
        Listener->>S3: writeStream(file_stream)
        S3-->>Listener: S3 Object Created
        
        Listener->>DB: INSERT into request_documents
        DB-->>Listener: Document Record Created
        
        Listener->>LocalDisk: delete(tmp_path)
        LocalDisk-->>Listener: Cleanup Complete
    end
    
    Listener->>Listener: Log Success
```

### File Validation Rules

```mermaid
flowchart TD
    Start[File Upload] --> CountCheck{Count <= 5?}
    CountCheck -->|No| ErrorCount[Error: Max 5 files]
    CountCheck -->|Yes| Loop[For Each File]
    
    Loop --> SizeCheck{Size <= 10MB?}
    SizeCheck -->|No| ErrorSize[Error: Max 10MB]
    SizeCheck -->|Yes| TypeCheck{Valid MIME?}
    
    TypeCheck -->|No| ErrorType[Error: Invalid type]
    TypeCheck -->|Yes| VirusCheck{Virus Scan}
    
    VirusCheck -->|Infected| ErrorVirus[Error: Malware detected]
    VirusCheck -->|Clean| Accept[Accept File]
    
    Accept --> MoreFiles{More Files?}
    MoreFiles -->|Yes| Loop
    MoreFiles -->|No| Success[All Files Valid]
    
    ErrorCount --> Reject[Reject Upload]
    ErrorSize --> Reject
    ErrorType --> Reject
    ErrorVirus --> Reject
    
    Reject --> End([Upload Failed])
    Success --> End([Upload Successful])
    
    style Success fill:#4caf50
    style Accept fill:#81c784
    style Reject fill:#f44336
```

### Supported File Types

| Category | MIME Types | Extensions |
|----------|-----------|-----------|
| **Documents** | application/pdf | .pdf |
| **Images** | image/jpeg, image/png | .jpg, .jpeg, .png |
| **Spreadsheets** | application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet | .xls, .xlsx |
| **Text** | text/plain, application/msword | .txt, .doc, .docx |

---

## 4. Email Notification System

### Email Dispatch Flow

```mermaid
flowchart TD
    Start[Event Fired] --> Listener[Queued Listener]
    Listener --> BuildMail[Build Mailable Class]
    
    BuildMail --> MailType{Email Type}
    
    MailType -->|Request Created| RCMail[RequestCreatedMail]
    MailType -->|Request Approved| RAMail[RequestApprovedMail]
    MailType -->|Request Rejected| RRMail[RequestRejectedMail]
    
    RCMail --> SetRecipient[To: Approver]
    RAMail --> SetRecipient2[To: Requester]
    RRMail --> SetRecipient2
    
    SetRecipient --> LoadData[Load Request Data]
    SetRecipient2 --> LoadData
    
    LoadData --> RenderView[Render Blade Template]
    RenderView --> MailDriver{Mail Driver}
    
    MailDriver -->|log| LogFile[storage/logs/laravel.log]
    MailDriver -->|smtp| SMTP[Send via SMTP]
    MailDriver -->|ses| SES[Send via AWS SES]
    
    LogFile --> Success[Email Logged]
    SMTP --> Success[Email Sent]
    SES --> Success
    
    Success --> UpdateDB[(Log in telescope_entries)]
    UpdateDB --> End([Complete])
    
    style Success fill:#4caf50
```

### Email Templates

#### Request Created Email

**To:** Approver  
**Subject:** New Request Requires Your Approval  
**Content:**
- Request ID and Type
- Requester name
- Summary of request
- Link to approval page
- Due date (if applicable)

#### Request Approved Email

**To:** Requester  
**Subject:** Your Request Has Been Approved  
**Content:**
- Request ID and Type
- Approver name and role
- Comments (if any)
- Next steps (if workflow continues)
- Final approval notice (if complete)

#### Request Rejected Email

**To:** Requester  
**Subject:** Your Request Has Been Rejected  
**Content:**
- Request ID and Type
- Rejector name and role
- Rejection reason
- Next steps or resubmission instructions

---

## 5. Workflow Configuration

### Workflow Creation Flow

```mermaid
flowchart TD
    Start([Admin Creates Workflow]) --> SelectRT[Select Request Type]
    SelectRT --> CheckExisting{Existing Workflow?}
    CheckExisting -->|Yes| Error[Error: Type has workflow]
    CheckExisting -->|No| EnterName[Enter Workflow Name]
    
    EnterName --> AddStep[Add Workflow Step]
    AddStep --> StepOrder[Set step_order]
    StepOrder --> SelectRole[Select Required Role]
    
    SelectRole --> SaveStep[(INSERT workflow_step)]
    SaveStep --> MoreSteps{Add Another Step?}
    
    MoreSteps -->|Yes| AddStep
    MoreSteps -->|No| ValidateOrder{Sequential Order?}
    
    ValidateOrder -->|No| ErrorOrder[Error: Steps must be 1,2,3...]
    ValidateOrder -->|Yes| SaveWorkflow[(INSERT workflow)]
    
    SaveWorkflow --> Success[Workflow Created]
    Success --> EnableType[Request Type Now Usable]
    EnableType --> End([Configuration Complete])
    
    Error --> End
    ErrorOrder --> End
    
    style SaveWorkflow fill:#4caf50
    style Success fill:#81c784
```

### Dynamic Workflow Routing

```mermaid
graph TD
    Request[New Request] --> WF[Load Workflow]
    WF --> Steps[(Get All Steps ORDER BY step_order)]
    
    Steps --> Current{Current Step}
    Current -->|Step 1| Role1{Required Role}
    Current -->|Step 2| Role2{Required Role}
    Current -->|Step 3| Role3{Required Role}
    
    Role1 -->|Manager| FindMgr[Find User's Manager]
    Role1 -->|HR| FindHR[Find HR User]
    Role1 -->|Finance| FindFin[Find Finance User]
    
    Role2 -->|Manager| FindMgr
    Role2 -->|HR| FindHR
    Role2 -->|Finance| FindFin
    
    Role3 -->|Manager| FindMgr
    Role3 -->|HR| FindHR
    Role3 -->|Finance| FindFin
    
    FindMgr --> CreateApproval[Create Approval Record]
    FindHR --> CreateApproval
    FindFin --> CreateApproval
    
    CreateApproval --> Notify[Send Email Notification]
    Notify --> Done[Awaiting Approval]
    
    style WF fill:#ffeb3b
    style CreateApproval fill:#4caf50
```

---

## Feature Flow Summary

### Request Lifecycle

1. **Creation** → User submits via dynamic form
2. **Validation** → Client + server validation
3. **Workflow Init** → First step determined
4. **Approver Assignment** → Based on role hierarchy
5. **Event Dispatch** → Async notifications
6. **Approval Processing** → Sequential or rejection
7. **Completion** → Final status update

### Key Performance Metrics

| Metric | Target |
|--------|--------|
| **Request Submission** | < 500ms |
| **Email Delivery** | < 30s (async) |
| **S3 Upload** | < 60s per file (async) |
| **Approval Processing** | < 300ms |
| **Workflow Transition** | < 200ms |

### Error Handling

All features implement:
- ✅ **Validation** at client and server
- ✅ **Transaction safety** for critical operations
- ✅ **Retry logic** for async jobs (3 attempts)
- ✅ **Logging** via Telescope and Laravel Log
- ✅ **User feedback** via API responses and emails

---

## Conclusion

FlowManager's feature flows prioritize:

- **User Experience** - Instant responses with background processing
- **Data Integrity** - Transaction-based state changes
- **Scalability** - Async job processing via queues
- **Observability** - Comprehensive logging and monitoring
- **Flexibility** - Dynamic forms and configurable workflows
