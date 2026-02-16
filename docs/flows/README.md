# FlowManager - Application Flows Documentation

This directory contains comprehensive documentation of all application flows, user journeys, and system architecture diagrams for the FlowManager application.

## 📚 Documentation Structure

### [1. User Journeys](01-user-journeys.md)
Complete user journey flows for all system roles:
- **Admin** - System configuration and management
- **HR** - Human resources workflows
- **Managers** - Team and approval management
- **Employees** - Request submission and tracking

### [2. System Architecture Flow](02-system-architecture.md)
High-level system architecture and component interactions:
- Application layers
- Service communication
- Event-driven architecture
- Background job processing

### [3. Feature Flows](03-feature-flows.md)
Detailed flows for core features:
- Request creation and management
- Approval workflow processing
- Document upload and storage
- Email notification system

### [4. Data Flow](04-data-flow.md)
Data movement and transformations:
- Request lifecycle data flow
- Database transaction flows
- Queue job data flow
- Event-listener data flow

### [5. Authentication & Authorization](05-authentication.md)
Security and access control flows:
- User authentication
- Role-based access control
- Permission verification
- API token management

### [6. Key Flows Summary](06-summary.md)
Quick reference guide with flow summaries and decision trees

---

## 🎯 Purpose

This documentation serves as:
- **Developer Reference** - Understanding system behavior
- **Onboarding Material** - New team member training
- **Architecture Documentation** - System design reference
- **Troubleshooting Guide** - Debugging complex workflows

## 🔍 How to Use

1. Start with the **Summary** for a quick overview
2. Dive into **User Journeys** to understand role-specific workflows
3. Review **System Architecture** for technical implementation
4. Study **Feature Flows** for detailed process documentation
5. Reference **Data Flow** for understanding state changes
6. Check **Authentication** for security implementation

## 📊 Diagram Legend

All flowcharts use Mermaid syntax and follow these conventions:

- **Rectangles** - Process/Action steps
- **Diamonds** - Decision points
- **Rounded Rectangles** - Start/End points
- **Cylinders** - Database operations
- **Parallelograms** - Data/Input
- **Dashed Lines** - Asynchronous operations

---

Last Updated: 2026-02-16
