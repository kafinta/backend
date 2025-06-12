# Project Documentation

## Structure
```
docs/
├── README.md                 # This file
├── systems/                  # Core system documentation
│   ├── auth/                 # Authentication system
│   │   ├── README.md        # Overview and quick start
│   │   ├── api.md           # API documentation
│   │   ├── frontend.md      # Frontend integration guide
│   │   └── roadmap.md       # Post-MVP features and timeline
│   ├── orders/              # Order management system
│   │   ├── README.md
│   │   ├── api.md
│   │   ├── frontend.md
│   │   └── roadmap.md
│   └── products/            # Product management system
│       ├── README.md
│       ├── api.md
│       ├── frontend.md
│       └── roadmap.md
├── features/                # Feature-specific documentation
│   ├── email/              # Email system features
│   │   ├── README.md
│   │   ├── templates.md
│   │   └── roadmap.md
│   ├── notifications/      # Notification system
│   │   ├── README.md
│   │   ├── channels.md
│   │   └── roadmap.md
│   └── payments/          # Payment system
│       ├── README.md
│       ├── providers.md
│       └── roadmap.md
└── development/           # Development guides
    ├── setup.md          # Development environment setup
    ├── testing.md        # Testing guidelines
    └── deployment.md     # Deployment procedures
```

## Documentation Guidelines

### System Documentation
Each system documentation should include:
1. **README.md**
   - System overview
   - Quick start guide
   - Key features
   - Dependencies

2. **api.md**
   - API endpoints
   - Request/response formats
   - Authentication requirements
   - Rate limiting
   - Error handling

3. **frontend.md**
   - Integration guidelines
   - Example code
   - Best practices
   - Common pitfalls

4. **roadmap.md**
   - Post-MVP features
   - Implementation timeline
   - Technical requirements
   - Dependencies

### Feature Documentation
Each feature documentation should include:
1. **README.md**
   - Feature overview
   - Use cases
   - Configuration

2. **Specific Documentation**
   - Implementation details
   - Integration points
   - Configuration options

3. **roadmap.md**
   - Future enhancements
   - Planned improvements
   - Technical debt

### Development Documentation
Development guides should include:
1. **setup.md**
   - Environment setup
   - Required tools
   - Configuration

2. **testing.md**
   - Testing strategies
   - Test cases
   - CI/CD integration

3. **deployment.md**
   - Deployment procedures
   - Environment configuration
   - Monitoring setup

## API Testing with Postman

### Collection Structure
The project uses a hierarchical Postman collection structure:
```
Kafinta API Tests (Parent Collection)
├── Authentication Tests
├── Order Management Tests
├── Product Management Tests
└── Feature Tests
    ├── Email Tests
    ├── Notification Tests
    └── Payment Tests
```

### Environment Setup
1. **Base Environment Variables**
   ```json
   {
       "base_url": "http://localhost:8000",
       "api_version": "v1",
       "debug_mode": true
   }
   ```

2. **Authentication Variables**
   ```json
   {
       "email": "test@example.com",
       "password": "Test123!@#",
       "access_token": "",
       "refresh_token": ""
   }
   ```

### Testing Guidelines
1. **Request Headers**
   ```
   Content-Type: application/json
   Accept: application/json
   X-Requested-With: XMLHttpRequest
   ```

2. **Cookie Management**
   - Enable cookies in Postman
   - Use the same domain for all requests
   - Check cookie settings in debug routes

3. **Development Environment**
   - Use debug routes for testing
   - Check simulated emails
   - Monitor request/response logs

### Best Practices
1. **Collection Organization**
   - Group related endpoints in folders
   - Use descriptive names for requests
   - Include request/response examples

2. **Environment Management**
   - Use separate environments for development/staging/production
   - Keep sensitive data in environment variables
   - Document all required variables

3. **Testing Workflow**
   - Start with authentication
   - Test endpoints in logical order
   - Verify error handling
   - Check rate limiting

4. **Debug Routes**
   - Use `/api/debug/*` endpoints for testing
   - Check authentication state
   - Verify cookie settings
   - Monitor email delivery

### Common Issues
1. **Authentication**
   - Cookie not being set
   - Token expiration
   - CSRF token issues

2. **Request Issues**
   - Missing required headers
   - Invalid JSON format
   - Rate limiting

3. **Environment Issues**
   - Incorrect base URL
   - Missing environment variables
   - Wrong API version

## Contributing
When adding new documentation:
1. Follow the established structure
2. Use clear and consistent formatting
3. Include code examples where relevant
4. Keep documentation up to date
5. Cross-reference related documentation 