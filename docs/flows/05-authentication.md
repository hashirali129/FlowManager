# Authentication & Authorization - FlowManager

Complete authentication and authorization flows including role-based access control and permission verification.

---

## Table of Contents

1. [Authentication Flow](#1-authentication-flow)
2. [Role-Based Access Control](#2-role-based-access-control)
3. [Permission Verification](#3-permission-verification)
4. [API Token Management](#4-api-token-management)
5. [Security Patterns](#5-security-patterns)

---

## 1. Authentication Flow

### User Registration

```mermaid
flowchart TD
    Start([User Signs Up]) --> Input[Enter Details]
    Input --> Validate{Validation}
    Validate -->|Invalid| Errors[Show Errors]
    Errors --> Input
    Validate -->|Valid| CheckEmail{Email Exists?}
    
    CheckEmail -->|Yes| DuplicateError[Error: Email taken]
    CheckEmail -->|No| HashPass[Hash Password]
    
    HashPass --> CreateUser[(INSERT INTO users)]
    CreateUser --> AssignRole[Assign Default Role: employee]
    AssignRole --> SendVerif[Send Verification Email]
    SendVerif --> Response[201 Created]
    Response --> LoginPrompt[Prompt to Login]
    
    DuplicateError --> End([Registration Failed])
    LoginPrompt --> End([Registration Complete])
    
    style CreateUser fill:#4caf50
    style AssignRole fill:#4caf50
```

### User Login

```mermaid
flowchart TD
    Start([User Login]) --> InputCreds[Enter Email & Password]
    InputCreds --> Submit[POST /api/auth/login]
    
    Submit --> ValidateInput{Input Valid?}
    ValidateInput -->|No| Error422[422 Validation Error]
    ValidateInput -->|Yes| FindUser[(SELECT FROM users WHERE email)]
    
    FindUser --> UserExists{User Found?}
    UserExists -->|No| Error401[401 Invalid Credentials]
    UserExists -->|Yes| VerifyPass{Verify Password}
    
    VerifyPass -->|Fail| Attempt{Attempts}
    VerifyPass -->|Success| CreateToken[Create Sanctum Token]
    
    Attempt -->|< 5| Error401
    Attempt -->|>= 5| Lockout[Lock Account 15 min]
    Lockout --> Error401
    
    CreateToken --> LoadUser[Load User with Roles]
    LoadUser --> Response["user: data, token: xxx"]
    Response --> StoreToken[Client Stores Token]
    StoreToken --> End([Authenticated])
    
    Error422 --> End
    Error401 --> End
    
    style CreateToken fill:#4caf50
    style Response fill:#81c784
```

### Authenticated Request

```mermaid
sequenceDiagram
    autonumber
    participant Client
    participant API
    participant Sanctum as Auth Middleware
    participant DB as Database
    participant Controller
    
    Client->>API: GET /api/requests
    Note over Client,API: Header: Authorization: Bearer {token}
    
    API->>Sanctum: Verify Token
    Sanctum->>DB: SELECT FROM personal_access_tokens
    DB-->>Sanctum: Token record
    
    alt Token Invalid
        Sanctum-->>API: 401 Unauthenticated
        API-->>Client: 401 Response
    else Token Valid
        Sanctum->>DB: SELECT user with roles
        DB-->>Sanctum: User object
        Sanctum-->>API: User authenticated
        API->>Controller: Execute request
        Controller-->>API: Response data
        API-->>Client: 200 OK
    end
```

---

## 2. Role-Based Access Control

### Role Hierarchy

```mermaid
graph TD
    Admin[Admin] -->|can do| HR
    Admin -->|can do| Manager
    Admin -->|can do| Employee
    
    HR[HR] -->|can do| Manager
    HR -->|can do| Employee
    
    Manager[Manager] -->|can do| Employee
    
    Admin -.->|Permissions| P1[Manage Users]
    Admin -.->|Permissions| P2[Configure System]
    Admin -.->|Permissions| P3[View All Requests]
    
    HR -.->|Permissions| P4[Approve HR Requests]
    HR -.->|Permissions| P5[Manage Teams]
    HR -.->|Permissions| P6[Generate Reports]
    
    Manager -.->|Permissions| P7[Approve Team Requests]
    Manager -.->|Permissions| P8[View Team Data]
    
    Employee -.->|Permissions| P9[Create Requests]
    Employee -.->|Permissions| P10[View Own Requests]
    
    style Admin fill:#f44336
    style HR fill:#ff9800
    style Manager fill:#2196f3
    style Employee fill:#4caf50
```

### Role Assignment Flow

```mermaid
flowchart TD
    Start[Admin Assigns Role] --> SelectUser[Select User]
    SelectUser --> SelectRole[Select Role]
    SelectRole --> CheckExisting{User Has Role?}
    
    CheckExisting -->|Yes| AlreadyHas[Error: Already assigned]
    CheckExisting -->|No| Assign[(INSERT INTO model_has_roles)]
    
    Assign --> LoadPerms[Auto-Load Role Permissions]
    LoadPerms --> UpdateCache[Clear Permission Cache]
    UpdateCache --> Success[Role Assigned]
    Success --> UserNotified[Notify User]
    
    AlreadyHas --> End([Assignment Failed])
    UserNotified --> End([Role Active])
    
    style Assign fill:#4caf50
    style LoadPerms fill:#2196f3
```

---

## 3. Permission Verification

### Request Authorization Check

```mermaid
flowchart TD
    Request[Incoming Request] --> Auth{Authenticated?}
    Auth -->|No| Reject401[401 Unauthenticated]
    Auth -->|Yes| Policy{Policy Check}
    
    Policy -->|Defined| RunPolicy[Execute Policy]
    Policy -->|None| RoleCheck{Role Check}
    
    RunPolicy --> PolicyResult{Authorized?}
    PolicyResult -->|No| Reject403[403 Forbidden]
    PolicyResult -->|Yes| Allow[Process Request]
    
    RoleCheck -->|Required| CheckRole{Has Role?}
    RoleCheck -->|None| PermCheck{Permission Check}
    
    CheckRole -->|No| Reject403
    CheckRole -->|Yes| Allow
    
    PermCheck -->|Required| HasPerm{Has Permission?}
    PermCheck -->|None| Allow
    
    HasPerm -->|No| Reject403
    HasPerm -->|Yes| Allow
    
    Reject401 --> End([Request Denied])
    Reject403 --> End
    Allow --> End([Request Processed])
    
    style Reject401 fill:#f44336
    style Reject403 fill:#f44336
    style Allow fill:#4caf50
```

### Policy Evaluation

```mermaid
sequenceDiagram
    autonumber
    participant Controller
    participant Gate as Authorization Gate
    participant Policy as RequestPolicy
    participant User
    participant Request
    
    Controller->>Gate: authorize('view', request)
    Gate->>Policy: view(User, Request)
    
    Policy->>User: isAdmin()?
    alt Is Admin
        User-->>Policy: true
        Policy-->>Gate: true
        Gate-->>Controller: Authorized
    else Not Admin
        User-->>Policy: false
        Policy->>Request: request.user_id == user.id?
        
        alt Owns Request
            Request-->>Policy: true
            Policy-->>Gate: true
            Gate-->>Controller: Authorized
        else Doesn't Own
            Request-->>Policy: false
            Policy-->>Gate: false
            Gate-->>Controller: 403 Forbidden
        end
    end
```

### Approval Authorization

```mermaid
flowchart TD
    Start[Approve Request] --> LoadApproval[(Load RequestApproval)]
    LoadApproval --> CheckStatus{Status = pending?}
    CheckStatus -->|No| Error[Error: Already processed]
    CheckStatus -->|Yes| GetStep[Get WorkflowStep]
    
    GetStep --> RequiredRole[Get required_role]
    RequiredRole --> UserHasRole{User Has Role?}
    
    UserHasRole -->|No| Forbidden[403 Forbidden]
    UserHasRole -->|Yes| IsApprover{Is Designated Approver?}
    
    IsApprover -->|No - Already assigned| Forbidden
    IsApprover -->|Yes| CheckTeam{Approval for Team Member?}
    
    CheckTeam -->|Manager Role| VerifyManager{Is User's Manager?}
    CheckTeam -->|HR/Finance| Allow[Authorized]
    
    VerifyManager -->|No| Forbidden
    VerifyManager -->|Yes| Allow
    
    Allow --> ProcessApproval[Process Approval]
    
    Error --> End([Denied])
    Forbidden --> End
    ProcessApproval --> End([Approved])
    
    style Forbidden fill:#f44336
    style Allow fill:#4caf50
    style ProcessApproval fill:#81c784
```

---

## 4. API Token Management

### Token Creation

```mermaid
flowchart TD
    Login[Successful Login] --> CreateToken[User::createToken]
    CreateToken --> GenToken[Generate Random Token]
    GenToken --> HashToken[Hash Token SHA-256]
    HashToken --> Store[(INSERT INTO personal_access_tokens)]
    
    Store --> TokenData["tokenable_id, name, token, abilities"]
    
    TokenData --> ReturnPlain[Return Plain Token Once]
    ReturnPlain --> Response["token: xxx|plaintext"]
    
    style GenToken fill:#ff9800
    style HashToken fill:#ff9800
    style Store fill:#4caf50
```

### Token Verification

```mermaid
flowchart TD
    Request[API Request] --> Extract[Extract Bearer Token]
    Extract --> Parse{Parse Token}
    Parse -->|Invalid Format| Reject[401 Unauthenticated]
    Parse -->|Valid| Hash[Hash Token]
    
    Hash --> Lookup[(SELECT FROM personal_access_tokens)]
    Lookup --> Found{Token Found?}
    Found -->|No| Reject
    Found -->|Yes| CheckExpiry{Expired?}
    
    CheckExpiry -->|Yes| Reject
    CheckExpiry -->|No| LoadUser[(Load User)]
    LoadUser --> SetAuth[Set authenticated user]
    SetAuth --> Continue[Continue Request]
    
    Reject --> End([Denied])
    Continue --> End([Authenticated])
    
    style Lookup fill:#2196f3
    style SetAuth fill:#4caf50
```

### Token Revocation

```mermaid
flowchart TD
    Logout[POST /api/auth/logout] --> GetToken[Get Current Token]
    GetToken --> Delete[(DELETE FROM personal_access_tokens)]
    Delete --> Response[200 OK]
    Response --> ClientClear[Client Clears Token]
    ClientClear --> End([Logged Out])
    
    style Delete fill:#f44336
```

---

## 5. Security Patterns

### Multi-Layer Security

```mermaid
graph TB
    subgraph "Network Layer"
        HTTPS[HTTPS/TLS]
        CORS[CORS Policy]
    end
    
    subgraph "Application Layer"
        CSRF[CSRF Protection]
        RateLimit[Rate Limiting]
    end
    
    subgraph "Authentication Layer"
        Sanctum[Laravel Sanctum]
        Sessions[Session Management]
    end
    
    subgraph "Authorization Layer"
        Gates[Authorization Gates]
        Policies[Model Policies]
        Roles[Spatie Roles]
        Perms[Spatie Permissions]
    end
    
    subgraph "Data Layer"
        Encryption[Field Encryption]
        QueryBinding[Parameter Binding]
    end
    
    HTTPS --> CSRF
    CORS --> RateLimit
    CSRF --> Sanctum
    RateLimit --> Sanctum
    Sanctum --> Gates
    Sessions --> Policies
    Gates --> Roles
    Policies --> Perms
    Roles --> Encryption
    Perms --> QueryBinding
    
    style Sanctum fill:#4caf50
    style Roles fill:#2196f3
    style Perms fill:#2196f3
```

### Password Security

```mermaid
flowchart TD
    Input[User Enters Password] --> Validate{Validation}
    Validate -->|Too Short| Reject[Error: Min 8 chars]
    Validate -->|Weak| Reject2[Error: Must have special char]
    Validate -->|Valid| Hash[bcrypt with cost=12]
    
    Hash --> Store[(Store in users.password)]
    Store --> LoginAttempt[Future Login Attempt]
    LoginAttempt --> Compare[bcrypt::check]
    Compare --> Match{Matches?}
    Match -->|Yes| Allow[Grant Access]
    Match -->|No| Deny[Deny Access]
    
    style Hash fill:#ff9800
    style Store fill:#4caf50
    style Allow fill:#4caf50
    style Deny fill:#f44336
```

### XSS & SQL Injection Prevention

| Attack Vector | Prevention |
|--------------|-----------|
| **SQL Injection** | Eloquent ORM with parameter binding |
| **XSS** | Blade template auto-escaping |
| **CSRF** | Token verification on mutations |
| **Mass Assignment** | `$fillable` / `$guarded` in models |
| **Insecure Deserialization** | Queue job signature verification |

---

## Authorization Matrix

### Role Permissions

| Action | Admin | HR | Manager | Employee |
|--------|-------|-----|---------|----------|
| **Create Request** | ✅ | ✅ | ✅ | ✅ |
| **View Own Requests** | ✅ | ✅ | ✅ | ✅ |
| **View All Requests** | ✅ | ✅ | ❌ | ❌ |
| **View Team Requests** | ✅ | ✅ | ✅ | ❌ |
| **Approve as Manager** | ❌ | ❌ | ✅ | ❌ |
| **Approve as HR** | ❌ | ✅ | ❌ | ❌ |
| **Configure Workflows** | ✅ | ❌ | ❌ | ❌ |
| **Manage Users** | ✅ | ✅ | ❌ | ❌ |
| **Manage Teams** | ✅ | ✅ | ❌ | ❌ |
| **View Horizon** | ✅ | ❌ | ❌ | ❌ |
| **View Telescope** | ✅ | ❌ | ❌ | ❌ |

---

## Security Best Practices

### Implemented Measures

1. **Authentication**
   - Sanctum token-based auth
   - Bcrypt password hashing (cost 12)
   - Account lockout after 5 failed attempts

2. **Authorization**
   - Policy-based access control
   - Role-based permissions (Spatie)
   - Owner-based resource access

3. **Data Protection**
   - HTTPS enforced
   - Sensitive data encrypted at rest
   - SQL injection prevention via ORM

4. **Session Security**
   - HTTP-only cookies
   - CSRF token validation
   - Session expiration (120 min)

5. **API Security**
   - Rate limiting (60 req/min)
   - CORS whitelist
   - Token expiration (optional)

---

## Conclusion

FlowManager's authentication and authorization system ensures:

- ✅ **Secure Authentication** - Token-based with password hashing
- ✅ **Granular Authorization** - Role and policy-based access
- ✅ **Defense in Depth** - Multiple security layers
- ✅ **Audit Trail** - All auth events logged via Telescope
- ✅ **Standards Compliance** - OWASP best practices
