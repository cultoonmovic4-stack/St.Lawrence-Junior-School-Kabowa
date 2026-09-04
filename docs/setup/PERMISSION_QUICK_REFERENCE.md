# Permission System Quick Reference Card

## 🚀 Quick Start

### 1. Setup (One-time)
```bash
mysql -u root -p st_lawrence_school < backend/api/setup/populate_permissions.sql
```

### 2. In PHP API Files
```php
require_once '../middleware/permission_middleware.php';
requirePermission('permission.name');
```

### 3. In JavaScript
```javascript
if (hasPermission('permission.name')) {
    // Show feature
}
```

---

## 📋 Common Permissions

| Action | Permission Name |
|--------|----------------|
| View users | `user.view` |
| Create user | `user.create` |
| Edit user | `user.edit` |
| Delete user | `user.delete` |
| View students | `student.view` |
| Edit students | `student.edit` |
| View admissions | `admission.view` |
| Approve admissions | `admission.approve` |
| View finances | `finance.view` |
| Record payments | `finance.payment.record` |
| Mark attendance | `academic.attendance.mark` |
| Enter results | `academic.results.enter` |
| Manage library | `library.upload` |
| Send messages | `comm.message.send` |

---

## 🎭 Role Levels

| Role | Level | Access |
|------|-------|--------|
| Super Administrator | 100 | Everything |
| Administrator | 80 | Most features |
| Accountant | 60 | Finance only |
| Teacher | 50 | Academic features |
| Librarian | 40 | Library only |
| Receptionist | 30 | Front desk |
| Parent | 10 | View only |
| Student | 5 | Read only |

---

## 💻 Code Examples

### PHP - Check Permission
```php
// Require permission (exits if denied)
requirePermission('user.create');

// Check permission (returns boolean)
if (hasPermission('user.edit')) {
    // Allow action
}

// Check role level
if (hasMinimumRoleLevel(80)) {
    // Administrator and above
}

// Check module access
if (canAccessModule('finance')) {
    // Show finance features
}
```

### JavaScript - Check Permission
```javascript
// Check permission
if (hasPermission('user.create')) {
    document.getElementById('createBtn').style.display = 'block';
}

// Check module access
if (canAccessModule('user_management')) {
    // Show menu item
}

// Check if super admin
if (isSuperAdmin) {
    // Show all features
}
```

---

## 🔧 Module Names

- `user_management`
- `student_management`
- `staff_management`
- `admissions`
- `finance`
- `academics`
- `library`
- `content`
- `communications`
- `reports`
- `settings`
- `dashboard`

---

## 📝 Permission Format

**Pattern:** `module.action`

**Examples:**
- `user.view`
- `student.create`
- `finance.payment.record`
- `academic.attendance.mark`

---

## ⚡ Quick Commands

### Check User Permissions
```sql
SELECT p.permission_name, p.permission_module
FROM role_permissions rp
JOIN permissions p ON rp.permission_id = p.id
WHERE rp.role_id = (SELECT role_id FROM users WHERE id = USER_ID);
```

### Add Permission to Role
```sql
INSERT INTO role_permissions (role_id, permission_id)
VALUES (ROLE_ID, PERMISSION_ID);
```

### Remove Permission from Role
```sql
DELETE FROM role_permissions 
WHERE role_id = ROLE_ID AND permission_id = PERMISSION_ID;
```

---

## 🐛 Debug Checklist

- [ ] SQL script executed successfully
- [ ] User has role_id assigned
- [ ] Session contains role_level
- [ ] Permission middleware included
- [ ] Permission name matches database
- [ ] User logged in after setup

---

## 📞 Need Help?

1. Check PERMISSION_SETUP_GUIDE.md
2. Review permission_middleware.php
3. Check browser console for errors
4. Verify database permissions table

---

**Print this card and keep it handy!** 📌
