---
paths:
  - 'app/OpenApi/**'
---

# Open Api

## Keep OpenAPI operations out of controllers
Put Swagger/OpenAPI operation attributes in dedicated classes under app/OpenApi. Controllers should stay focused on request handling and should not carry large OA attribute blocks.
