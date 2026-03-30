# GitHub Copilot Customization

This project includes GitHub Copilot skills and instructions to enhance your development experience with project-specific guidance.

## 📁 What's Included

### Workspace Instructions
**File**: `.github/copilot-instructions.md`

Always-on project guidelines that apply to every conversation:
- Multi-tenant architecture principles
- Code standards (Laravel, React, Next.js)
- Testing requirements
- API design patterns
- Security requirements
- Common pitfalls to avoid

### Skills

Specialized, on-demand workflows for specific tasks. Type `/` in Copilot Chat to see available skills.

#### 1. E-Commerce API Documentation (`/ecommerce-api-docs`)
**Use when**: Creating or updating API endpoints

- Automatic API documentation generation with Laravel Scribe
- PHPDoc annotation templates
- Step-by-step controller documentation
- Best practices for API docs

**Quick action**: Document a new controller and generate docs

#### 2. E-Commerce Multi-Tenancy (`/ecommerce-tenancy`)
**Use when**: Implementing tenant-aware features

- Multi-tenant model patterns
- Global scope implementation
- Tenant isolation testing
- Security best practices
- Data leakage prevention

**Quick action**: Create a tenant-aware model with proper isolation

#### 3. E-Commerce Setup (`/ecommerce-setup`)
**Use when**: Setting up development environment

- Complete setup guide (backend, admin, storefront)
- Repository structure creation
- Client storefront creation
- Environment configuration
- Troubleshooting common issues

**Quick action**: Setup a new development environment

## 🚀 How to Use

### Workspace Instructions (Automatic)

These guidelines are **automatically applied** to all your Copilot conversations in this workspace. No action needed!

### Skills (On-Demand)

1. **Via Slash Commands**: Type `/` in Copilot Chat to see available skills
2. **By Description**: Just ask naturally, e.g., "help me document my API controller"
3. **Explicit Invocation**: Type `/ecommerce-api-docs` to load the skill

### Examples

```
👤 "I need to create a new Product controller with tenant isolation"
🤖 Loads /ecommerce-tenancy skill and provides tenant-aware controller template

👤 "How do I document this controller for the API?"
🤖 Loads /ecommerce-api-docs skill and provides PHPDoc templates

👤 "Setup a new client storefront"
🤖 Loads /ecommerce-setup skill and walks through storefront creation
```

## 📚 What Each Skill Contains

### `/ecommerce-api-docs`
- Laravel Scribe setup automation
- Complete PHPDoc annotation guide
- CRUD controller documentation templates
- Standard error response formats
- Documentation generation commands
- Testing documentation examples

**Files**:
- `SKILL.md` - Main skill guide
- References project docs for detailed guides

### `/ecommerce-tenancy`
- Multi-tenant architecture patterns
- Model, controller, middleware examples
- Database migration patterns
- Tenant isolation testing examples
- Security checklists
- Common mistakes and solutions

**Files**:
- `SKILL.md` - Main skill guide
- References multi-tenancy documentation

### `/ecommerce-setup`
- Complete environment setup (backend, admin, storefront)
- Repository structure initialization
- Client storefront creation workflow
- Configuration guides
- Troubleshooting common issues
- Daily development workflow

**Files**:
- `SKILL.md` - Main skill guide
- References getting-started documentation

## 🎯 Benefits

### For You
- **Faster Development**: Ready-made templates and patterns
- **Fewer Bugs**: Security best practices built-in
- **Consistent Code**: Project standards automatically applied
- **Better Documentation**: Auto-generated, always up-to-date

### For Your Team
- **Onboarding**: New developers get instant project context
- **Standards**: Everyone follows the same patterns
- **Knowledge Sharing**: Best practices are codified
- **Less Review Time**: Code follows standards from the start

## 🔧 Customization

### Adding Your Own Skills

1. Create new folder: `.github/skills/your-skill-name/`
2. Create `SKILL.md` with YAML frontmatter:

```markdown
---
name: your-skill-name
description: 'What it does. Use when: specific scenarios.'
---

# Your Skill Name

## When to Use
...

## Procedures
...
```

3. Copilot will automatically discover and use it!

### Updating Workspace Instructions

Edit `.github/copilot-instructions.md` to add/modify project-wide guidelines.

**Note**: Keep it focused on what's truly needed for *every* task. Don't make it a knowledge dump.

## 📖 Related Documentation

### Core Documentation
- [Getting Started Guide](../docs/11-getting-started.md) - Development setup
- [System Architecture](../docs/01-system-architecture.md) - High-level design
- [Multi-Tenancy Strategy](../docs/07-multi-tenancy.md) - Tenant isolation
- [API Documentation System](../docs/16-api-documentation-system.md) - API docs

### Quick References
- [API Docs Quick Reference](../docs/API-DOCS-QUICK-REFERENCE.md) - Copy-paste templates
- [API Docs Overview](../docs/API-DOCS-OVERVIEW.md) - 2-minute guide

### Setup Scripts
- `scripts/setup-repos.bat/sh` - Initialize repository structure
- `scripts/setup-api-docs.bat/sh` - Setup API documentation
- `scripts/create-client-store.bat/sh` - Create client storefront

## 🎓 Learning Path

### New to the Project?

1. **Read**: Workspace instructions (automatic)
2. **Setup**: Use `/ecommerce-setup` skill
3. **Review**: Documentation in `docs/` folder
4. **Build**: Use `/ecommerce-tenancy` for features
5. **Document**: Use `/ecommerce-api-docs` for endpoints

### Common Workflows

**Creating a new API endpoint:**
1. Use `/ecommerce-tenancy` - Create tenant-aware controller
2. Implement business logic
3. Use `/ecommerce-api-docs` - Add PHPDoc annotations
4. Generate docs: `php artisan scribe:generate`
5. Test in `http://localhost:8000/docs`

**Setting up new client storefront:**
1. Use `/ecommerce-setup` skill
2. Follow client storefront creation steps
3. Customize theme in `theme/config.ts`
4. Test: `npm run dev`
5. Build: `npm run build`

## 💡 Tips

### For Better Results

✅ **Be Specific**: "Create a tenant-aware Product controller" vs "Create a controller"
✅ **Use Skill Names**: Mention skill names explicitly if needed
✅ **Check Examples**: Each skill has complete code examples
✅ **Reference Docs**: Skills link to detailed documentation

### Troubleshooting

**Skill not loading?**
- Type `/` to see if skill appears in list
- Check `name` in frontmatter matches folder name
- Restart VS Code if recently added

**Instructions not applying?**
- Check `.github/copilot-instructions.md` exists
- Verify YAML frontmatter (if used) is valid
- Try starting a new chat conversation

**Getting generic responses?**
- Be more specific about what you need
- Explicitly mention the skill: "Use /ecommerce-tenancy"
- Reference project patterns: "following our tenant isolation pattern"

## 🔒 Security Note

These skills include security best practices, especially around tenant isolation. Always:
- ✅ Test tenant isolation for new features
- ✅ Never trust client-provided `store_id`
- ✅ Use global scopes for automatic filtering
- ✅ Review security checklist in `/ecommerce-tenancy`

## 📊 Skill Coverage

| Area | Skill | Coverage |
|------|-------|----------|
| **API Documentation** | `/ecommerce-api-docs` | Laravel Scribe setup, PHPDoc templates, generation |
| **Multi-Tenancy** | `/ecommerce-tenancy` | Models, controllers, tests, isolation patterns |
| **Setup & Config** | `/ecommerce-setup` | Environment setup, repos, client creation |
| **General Guidelines** | Workspace Instructions | Code standards, architecture, security |

## 🎉 Success Stories

These skills codify lessons learned from building this platform:

- **API Documentation**: Saves 15-30 min per endpoint
- **Tenant Isolation**: Prevents security vulnerabilities
- **Setup Automation**: Onboard developers in < 1 hour
- **Code Consistency**: PRs pass review 3x faster

## 🤝 Contributing

When you discover a repeatable pattern or workflow:

1. Document it in relevant skill
2. Add examples from real code
3. Link to detailed docs if needed
4. Update this README

**Remember**: Skills should be actionable procedures, not just reference material.

---

**Questions?** Check the skills themselves - they contain extensive examples and step-by-step guides!
