# Role-Based Permission System Setup Guide
## St. Lawrence Junior School Management System

---

## 📋 Overview

This guide will help you set up the complete role-based permission system for the school management system.

---

## 🚀 Quick Setup (3 Steps)

### Step 1: Run the SQL Script

Execute the permission population script in your MySQL database:

```bash
# Option 1: Using MySQL command line
mysql -u your_username -p st_lawrence_school < backend/api/setup/populate_permissions.sql

# Option 2: Using phpMyAdmin
# 1. Open phpMyAdmin
# 2. Select 'st_lawrence_school' database
# 3. Click 'SQL' tab
# 4. Copy and paste the contents of populate_permissions.sql
# 5. Click 'Go'
```

This will:
- ✅ Create 60+ permissions across 11 modules
- ✅ Assign appropriate permissions to each role
- ✅ Set up the complete permission structure

### Step 2: Verify Installation

Run these verification queries in your database:

```sql
-- Check permissions count per role
SELECT 
    r.role_name,
    COUNT(rp.permission_id) as permission_count
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.role_name
ORDER BY r.role_level DESC;

-- Expected results:
-- Super Administrator: 60+ permissions
-- Administrator: 50+ permissions
-- Teacher: 15-20 permissions
-- Accountant: 10-15 permissions
-- Librarian: 5-7 permissions
-- Receptionist: 8-10 permissions
-- Parent: 8-10 permissions
-- Student: 5-7 permissions
```

### Step 3: Test the System

1. **Login as Super Administrator**
   - Username: `admin`
   - Password: `admin123`
   - Should see all menu items and features

2. **Create a Test User**
   - Go to User Management
   - Create a user with "Teacher" role
   - Logout and login with the new credentials
   - Verify that only teacher-related features are visible

---

## 📊 Permission Structure

### Modules and Their Permissions

#### 1. User Management (6 permissions)
- `user.view` - View users list
- `user.create` - Create new users
- `user.edit` - Edit user information
- `user.delete` - Delete users
- `user.manage_roles` - Assign roles to users
- `user.view_activity` - View user activity logs

#### 2. Student Management (5 permissions)
- `student.view` - View students list
- `student.create` - Add new students
- `student.edit` - Edit student information
- `student.delete` - Delete students
- `student.view_details` - View detailed student information

#### 3. Staff Management (4 permissions)
- `staff.view` - View staff list
- `staff.create` - Add new staff members
- `staff.edit` - Edit staff information
- `staff.delete` - Delete staff members

#### 4. Admissions (6 permissions)
- `admission.view` - View admission applications
- `admission.create` - Create admission applications
- `admission.edit` - Edit admission applications
- `admission.delete` - Delete admission applications
- `admission.approve` - Approve/reject applications
- `admission.review` - Review and update application status

#### 5. Finance (7 permissions)
- `finance.view` - View financial records
- `finance.fees.manage` - Manage fee structure
- `finance.payment.record` - Record fee payments
- `finance.payment.view` - View payment records
- `finance.expense.create` - Record expenses
- `finance.expense.view` - View expenses
- `finance.reports` - Generate financial reports

#### 6. Academics (12 permissions)
- `academic.class.view` - View classes
- `academic.class.manage` - Manage classes
- `academic.subject.view` - View subjects
- `academic.subject.manage` - Manage subjects
- `academic.timetable.view` - View timetable
- `academic.timetable.manage` - Manage timetable
- `academic.exam.view` - View exams
- `academic.exam.manage` - Manage exams
- `academic.results.view` - View exam results
- `academic.results.enter` - Enter exam results
- `academic.attendance.view` - View attendance
- `academic.attendance.mark` - Mark attendance

#### 7. Library (5 permissions)
- `library.view` - View library resources
- `library.upload` - Upload library resources
- `library.edit` - Edit library resources
- `library.delete` - Delete library resources
- `library.download` - Download library resources

#### 8. Content Management (10 permissions)
- `content.teacher.view` - View teachers
- `content.teacher.manage` - Manage teachers
- `content.gallery.view` - View gallery
- `content.gallery.manage` - Manage gallery
- `content.event.view` - View events
- `content.event.manage` - Manage events
- `content.testimonial.view` - View testimonials
- `content.testimonial.manage` - Manage testimonials
- `content.calendar.view` - View academic calendar
- `content.calendar.manage` - Manage academic calendar

#### 9. Communications (6 permissions)
- `comm.message.view` - View messages
- `comm.message.send` - Send messages
- `comm.message.reply` - Reply to messages
- `comm.notification.send` - Send notifications
- `comm.sms.send` - Send SMS
- `comm.email.send` - Send emails

#### 10. Reports (5 permissions)
- `report.student` - Generate student reports
- `report.financial` - Generate financial reports
- `report.academic` - Generate academic reports
- `report.attendance` - Generate attendance reports
- `report.custom` - Create custom reports

#### 11. Settings (4 permissions)
- `settings.view` - View system settings
- `settings.edit` - Edit system settings
- `settings.backup` - Backup system data
- `settings.restore` - Restore system data

#### 12. Dashboard (2 permissions)
- `dashboard.view` - View dashboard
- `dashboard.stats` - View statistics

---

## 🎭 Role Permissions Summary

### Super Administrator (Level 100)
**All 60+ permissions** - Complete system access

### Administrator (Level 80)
**50+ permissions** - All except:
- System backup/restore
- Delete users (can only deactivate)

### Teacher (Level 50)
**15-20 permissions** including:
- View and manage classes
- Mark attendance
- Enter exam results
- Upload library resources
- Send messages to parents
- View student information

### Accountant (Level 60)
**10-15 permissions** including:
- Manage fee structure
- Record payments
- Manage expenses
- Generate financial reports
- View student information (for billing)

### Librarian (Level 40)
**5-7 permissions** including:
- Manage library resources
- Upload/edit/delete resources
- View download statistics

### Receptionist (Level 30)
**8-10 permissions** including:
- View admission applications
- Manage contact submissions
- Reply to messages
- View student information
- View events and calendar

### Parent (Level 10)
**8-10 permissions** including:
- View their children's information
- View attendance and results
- View fee statements
- Send messages to teachers
- Access library resources

### Student (Level 5)
**5-7 permissions** including:
- View their own information
- View attendance and results
- Access library resources
- View school events

---

## 🔧 How to Use in Code

### In PHP API Endpoints

```php
<?php
require_once '../middleware/auth_middleware.php';
require_once '../middleware/permission_middleware.php';

// Check if user is authenticated
requireAuth();

// Check specific permission
requirePermission('user.create');

// Check minimum role level
requireMinimumRoleLevel(80); // Administrator and above

// Check if user has permission (returns boolean)
if (hasPermission('student.edit')) {
    // Allow editing
}

// Check module access
if (canAccessModule('finance')) {
    // Show finance features
}
?>
```

### In JavaScript (Dashboard)

```javascript
// Check if user has permission
if (hasPermission('user.create')) {
    // Show create user button
}

// Check module access
if (canAccessModule('user_management')) {
    // Show user management menu
}

// Check if super admin
if (isSuperAdmin) {
    // Show all features
}
```

---

## 🧪 Testing Checklist

- [ ] Super Administrator can access everything
- [ ] Administrator cannot access system backup/restore
- [ ] Teacher can only see academic features
- [ ] Accountant can only see finance features
- [ ] Librarian can only see library features
- [ ] Receptionist has limited access
- [ ] Parent can only view their children's data
- [ ] Student has read-only access
- [ ] UI elements hide/show based on permissions
- [ ] API endpoints reject unauthorized requests

---

## 🔐 Security Best Practices

1. **Always check permissions on both frontend and backend**
   - Frontend: Hide UI elements
   - Backend: Validate permissions in API

2. **Use specific permissions, not just role levels**
   - ✅ Good: `requirePermission('user.delete')`
   - ❌ Bad: `if (role_level >= 80)`

3. **Log all permission-related actions**
   - User creation, deletion, role changes
   - Permission grants/revokes

4. **Regularly audit permissions**
   - Review who has what access
   - Remove unnecessary permissions

5. **Test with different roles**
   - Create test accounts for each role
   - Verify access restrictions work

---

## 📝 Adding New Permissions

To add a new permission:

1. **Insert into database:**
```sql
INSERT INTO permissions (permission_name, permission_module, description) 
VALUES ('feature.action', 'module_name', 'Description of permission');
```

2. **Assign to roles:**
```sql
INSERT INTO role_permissions (role_id, permission_id)
SELECT role_id, id FROM permissions WHERE permission_name = 'feature.action';
```

3. **Use in code:**
```php
requirePermission('feature.action');
```

---

## 🆘 Troubleshooting

### Issue: User can't access features they should have
**Solution:** 
1. Check if permissions are assigned to their role
2. Verify user's role_id is correct
3. Check if session has role_level stored
4. Clear browser cache and re-login

### Issue: Permission check always fails
**Solution:**
1. Verify permission_middleware.php is included
2. Check if session is started
3. Verify permission name matches database exactly
4. Check database connection

### Issue: UI elements not hiding
**Solution:**
1. Check browser console for JavaScript errors
2. Verify loadUserPermissions() is called
3. Check if configureUIByPermissions() runs
4. Verify element selectors are correct

---

## 📞 Support

For issues or questions:
1. Check the troubleshooting section above
2. Review the code comments in permission_middleware.php
3. Check the activity_logs table for permission errors
4. Contact system administrator

---

## ✅ Setup Complete!

Your role-based permission system is now fully configured and ready to use!

**Next Steps:**
1. Create users with different roles
2. Test each role's access
3. Customize permissions as needed
4. Train staff on the system

---

**Last Updated:** January 2026
**Version:** 1.0
