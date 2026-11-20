# Architecture

```mermaid
flowchart LR
    UI["Vue"]
    API["Laravel"]

    subgraph Backend ["Backend"]
        Modules["Workspaces, servers, projects"]
        Deployments["Deployments and audit history"]
        Queue["Queues / Horizon"]
        Infra[("PostgreSQL / Redis / Reverb")]
    end

    subgraph Target ["Managed servers"]
        Docker["Docker and applications"]
        Monitoring["Monitoring agent"]
    end

    UI -->|GraphQL| API
    API --> Modules
    API --> Deployments
    API --> Queue
    API --> Infra

    Queue -->|Secure communication| Docker
    Monitoring -.->|Metrics| API
```

## Modules

Planned modules:

| Module                 | What belongs there                          |
| ---------------------- | ------------------------------------------- |
| `Identity`             | App auth                                    |
| `Workspaces`           | Resource ownership, membership, roles       |
| `Servers`              | Server inventory, connections, availability |
| `Projects`             | Applications, repositories, environments    |
| `Deployments`          | Deployment requests, execution, history     |
| `ContainerRuntime`     | Docker integration and container operations |
| `Monitoring`           | Metrics, alerts                             |
| `Notifications`        | App notifications and                       |
| `ConfigurationBuilder` | `compose.yaml` and `Dockerfile` generation  |
| `Audit`                | Records of actions                          |
