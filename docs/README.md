# Kafinta Documentation

## 🚀 Quick Start for Frontend Developers

**👉 Start here: [Frontend Integration Guide](FRONTEND-GUIDE.md)**

This is your main resource for building the Nuxt.js frontend. It contains:
- Complete API documentation with examples
- Authentication setup and flows
- Product discovery and cart management
- Nuxt.js implementation examples
- Security best practices

## 📚 Additional Documentation

### For Frontend Development
- **[Frontend Guide](FRONTEND-GUIDE.md)** - Complete frontend integration guide ⭐
- **[API Quick Reference](API-QUICK-REFERENCE.md)** - Essential API endpoints cheat sheet 🚀
- **[API Reference](frontend-api-guide.md)** - Detailed API endpoint documentation
- **[Environment Setup](environment-setup.md)** - Development and production setup

### For Backend Development
- **[Email Queue System](systems/email-queue.md)** - Email system with queue reliability
- **[Seeder Images](systems/seeder-images.md)** - Image management for seeders

## 🎯 Clean Documentation Structure

The documentation has been streamlined for frontend development:

```
docs/
├── README.md                    # This file - start here
├── FRONTEND-GUIDE.md           # 🚀 Complete frontend integration guide
├── API-QUICK-REFERENCE.md      # ⚡ Essential endpoints cheat sheet
├── PRODUCTION-CHECKLIST.md     # 🔒 Deployment and security checklist
├── frontend-api-guide.md       # 📖 Detailed API documentation
├── environment-setup.md        # ⚙️ Environment configuration
└── systems/                    # Backend-specific docs (minimal)
    ├── email-queue.md          # Email system details
    └── seeder-images.md        # Image seeding system
```

## ✨ Why This Structure?

**Frontend-First Approach:**
- All essential information is in the main docs directory
- No need to navigate through complex folder structures
- Quick access to the most important guides
- Reduced cognitive load for frontend developers

**Consolidated Information:**
- Authentication, products, cart, and orders are all covered in the Frontend Guide
- API endpoints are consolidated in the Quick Reference
- Production deployment is covered in one comprehensive checklist

## 🧪 API Testing

### Quick Test with Postman
1. **Import the API endpoints** from the Quick Reference
2. **Set base URL**: `http://localhost:8000/api`
3. **Enable cookies** in Postman settings
4. **Test authentication flow**:
   - Register: `POST /user/signup`
   - Login: `POST /user/login`
   - Get profile: `GET /user/profile`

### Essential Headers
```
Content-Type: application/json
Accept: application/json
X-Requested-With: XMLHttpRequest
```

### Cookie-Based Authentication
- Cookies are automatically managed by Postman
- No need to manually handle tokens
- Just ensure cookies are enabled

## 🚀 Getting Started

1. **Read the [Frontend Guide](FRONTEND-GUIDE.md)** - Complete integration guide
2. **Use [API Quick Reference](API-QUICK-REFERENCE.md)** - For endpoint lookup
3. **Check [Environment Setup](environment-setup.md)** - For configuration
4. **Review [Production Checklist](PRODUCTION-CHECKLIST.md)** - Before deployment

## 💡 Need Help?

- **Frontend Integration**: See [FRONTEND-GUIDE.md](FRONTEND-GUIDE.md)
- **API Endpoints**: See [API-QUICK-REFERENCE.md](API-QUICK-REFERENCE.md)
- **Environment Setup**: See [environment-setup.md](environment-setup.md)
- **Production Deployment**: See [PRODUCTION-CHECKLIST.md](PRODUCTION-CHECKLIST.md)