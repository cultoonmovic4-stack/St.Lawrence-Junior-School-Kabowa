# 🎯 Permission System Setup Checklist

## ✅ Pre-Setup Verification

- [ ] Database `st_lawrence_school` exists
- [ ] MySQL/MariaDB is running
- [ ] You have database admin credentials
- [ ] PHP version 7.4 or higher
- [ ] Apache/Nginx web server running
- [ ] All project files are in place

---

## 📦 Step 1: Database Setup (5 minutes)

### Run the SQL Script

**Option A: Command Line**
```bash
mysql -u root -p st_lawrence_school < backend/api/setup/populate_permissions.sql
```

**Option B: phpMyAdmin**
1. [ ] Open phpMyAdmin
2. [ ] Select `st_lawrence_school` database
3. [ ] Click `SQL` tab
4. [ ] Copy contents of `populate_permissions.sql`
5. [ ] Paste and click `Go`

### Verify Installation
```sql
-- Should return 8 roles with permission counts
SELECT r.role_name, COUNT(rp.permission_id) as permissions
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.role_name
ORDER BY r.role_level DESC;
```

**Expected Results:**
- [ ] Super Administrator: 60+ permissions
- [ ] Administrator: 50+ permissions
- [ ] Accountant: 10-15 permissions
- [ ] Teacher: 15-20 permissions
- [ ] Librarian: 5-7 permissions
- [ ] Receptionist: 8-10 permissions
- [ ] Parent: 8-10 permissions
- [ ] Student: 5-7 permissions

---

## 🧪 Step 2: Test Super Administrator (5 minutes)

### Login as Admin
- [ ] Navigate to login page
- [ ] Username: `admin`
- [ ] Password: `admin123`
- [ ] Successfully logged in

### Verify Full Access
- [ ] Dashboard loads correctly
- [ ] All menu items visible
- [ ] User Management accessible
- [ ] Settings accessible
- [ ] No console errors

### Check Permissions
- [ ] Open browser console (F12)
- [ ] Look for "User permissions loaded" message
- [ ] Verify `isSuperAdmin: true`
- [ ] Verify `permissions: ['*']` or full list

---

## 👥 Step 3: Create Test Users (10 minutes)

### Create Teacher Account
- [ ] Go to User Management
- [ ] Click "Add User"
- [ ] Fill in details:
  - Username: `teacher1`
  - Full Name: `Test Teacher`
  - Email: `teacher@test.com`
  - Role: `Teacher`
  - Password: `teacher123`
- [ ] User created successfully

### Create Accountant Account
- [ ] Create user with:
  - Username: `accountant1`
  - Email: `accountant@test.com`
  - Role: `Accountant`
  - Password: `accountant123`

### Create Librarian Account
- [ ] Create user with:
  - Username: `librarian1`
  - Email: `librarian@test.com`
  - Role: `Librarian`
  - Password: `librarian123`

---

## 🔐 Step 4: Test Role Restrictions (15 minutes)

### Test Teacher Account
- [ ] Logout from admin
- [ ] Login as `teacher1` / `teacher123`
- [ ] Verify limited access:
  - [ ] Dashboard visible
  - [ ] Academic features visible
  - [ ] User Management HIDDEN
  - [ ] Settings HIDDEN
  - [ ] Finance HIDDEN
- [ ] Try accessing User Management directly
- [ ] Should see limited features only

### Test Accountant Account
- [ ] Logout
- [ ] Login as `accountant1` / `accountant123`
- [ ] Verify finance access:
  - [ ] Dashboard visible
  - [ ] Finance features visible (if implemented)
  - [ ] User Management HIDDEN
  - [ ] Academic features HIDDEN
  - [ ] Settings HIDDEN

### Test Librarian Account
- [ ] Logout
- [ ] Login as `librarian1` / `librarian123`
- [ ] Verify library access:
  - [ ] Dashboard visible
  - [ ] Library features visible
  - [ ] Everything else HIDDEN

---

## 🛡️ Step 5: Test API Protection (10 minutes)

### Test Permission Checks
- [ ] Login as teacher
- [ ] Open browser console
- [ ] Try to access User Management API:
```javascript
fetch('../api/users/list.php')
  .then(r => r.json())
  .then(d => console.log(d));
```
- [ ] Should return 403 Forbidden error
- [ ] Error message: "Access denied"

### Test Super Admin API Access
- [ ] Login as admin
- [ ] Try same API call
- [ ] Should return user list successfully

---

## 📱 Step 6: Test UI Elements (10 minutes)

### Verify Menu Hiding
- [ ] Login as different roles
- [ ] Check sidebar menu items
- [ ] Verify items hide/show correctly:

**Super Admin sees:**
- [ ] All menu items
- [ ] All submenu items
- [ ] All right rail icons

**Teacher sees:**
- [ ] Dashboard
- [ ] Academic features
- [ ] Library
- [ ] Messages
- [ ] NO User Management
- [ ] NO Settings

**Accountant sees:**
- [ ] Dashboard
- [ ] Finance features
- [ ] Reports
- [ ] NO User Management
- [ ] NO Academic features

---

## 🔍 Step 7: Verify Activity Logging (5 minutes)

### Check Activity Logs
```sql
SELECT * FROM activity_logs 
ORDER BY created_at DESC 
LIMIT 10;
```

- [ ] User login events logged
- [ ] User creation events logged
- [ ] IP addresses recorded
- [ ] Timestamps correct

---

## 📊 Step 8: Performance Check (5 minutes)

### Test Load Times
- [ ] Dashboard loads in < 2 seconds
- [ ] User Management loads in < 2 seconds
- [ ] Permission checks don't slow down pages
- [ ] No console errors
- [ ] No PHP errors in logs

### Check Database Queries
```sql
-- Check for slow queries
SHOW PROCESSLIST;

-- Check table sizes
SELECT 
    table_name,
    table_rows,
    ROUND(data_length / 1024 / 1024, 2) as size_mb
FROM information_schema.tables
WHERE table_schema = 'st_lawrence_school'
AND table_name IN ('permissions', 'role_permissions', 'activity_logs');
```

- [ ] Queries execute quickly
- [ ] Tables properly indexed
- [ ] No performance issues

---

## 📚 Step 9: Documentation Review (5 minutes)

### Read Documentation
- [ ] Read `PERMISSION_SETUP_GUIDE.md`
- [ ] Read `PERMISSION_QUICK_REFERENCE.md`
- [ ] Read `PERMISSION_SYSTEM_SUMMARY.md`
- [ ] Review `PERMISSION_STRUCTURE.txt`

### Understand Code
- [ ] Review `permission_middleware.php`
- [ ] Understand `hasPermission()` function
- [ ] Understand `requirePermission()` function
- [ ] Know how to add new permissions

---

## 🎓 Step 10: Train Your Team (Optional)

### Admin Training
- [ ] Show how to create users
- [ ] Explain role assignments
- [ ] Demonstrate permission system
- [ ] Show activity logs

### Developer Training
- [ ] Explain permission checks
- [ ] Show how to protect APIs
- [ ] Demonstrate UI hiding
- [ ] Review code examples

---

## ✅ Final Verification Checklist

### System Status
- [ ] All permissions created (60+)
- [ ] All roles configured (8)
- [ ] API endpoints protected
- [ ] UI elements hide/show correctly
- [ ] Activity logging works
- [ ] No errors in console
- [ ] No errors in PHP logs
- [ ] Performance is good

### Security Checks
- [ ] Super admin has full access
- [ ] Other roles have limited access
- [ ] Unauthorized API calls return 403
- [ ] Session validation works
- [ ] Password hashing works
- [ ] Activity logging captures events

### User Experience
- [ ] Login works smoothly
- [ ] Dashboard loads quickly
- [ ] Menu items appropriate for role
- [ ] No confusing error messages
- [ ] UI is clean and intuitive

---

## 🎉 Completion Certificate

```
╔══════════════════════════════════════════════════════════════╗
║                                                              ║
║         PERMISSION SYSTEM SETUP COMPLETE! ✅                 ║
║                                                              ║
║  Date: _____________________                                 ║
║  Completed by: _____________________                         ║
║                                                              ║
║  System Status: PRODUCTION READY                             ║
║                                                              ║
║  ✓ Database configured                                       ║
║  ✓ Permissions populated                                     ║
║  ✓ Roles assigned                                            ║
║  ✓ API protection enabled                                    ║
║  ✓ UI configured                                             ║
║  ✓ Testing completed                                         ║
║                                                              ║
║         🎊 Congratulations! 🎊                               ║
║                                                              ║
╚══════════════════════════════════════════════════════════════╝
```

---

## 🆘 Troubleshooting

### Issue: SQL script fails
**Solution:**
- Check database exists
- Verify MySQL credentials
- Check for syntax errors
- Try running in smaller chunks

### Issue: Permissions not working
**Solution:**
- Clear browser cache
- Logout and login again
- Check session has role_level
- Verify SQL script ran successfully

### Issue: UI not hiding elements
**Solution:**
- Check browser console for errors
- Verify `loadUserPermissions()` is called
- Check `configureUIByPermissions()` runs
- Verify element selectors are correct

### Issue: API returns 403 for admin
**Solution:**
- Check user's role_level in database
- Verify session contains role information
- Check permission_middleware.php is included
- Clear session and re-login

---

## 📞 Need Help?

1. Check troubleshooting section above
2. Review setup guide documentation
3. Check code comments in middleware files
4. Verify database permissions table
5. Check activity_logs for errors

---

## 🚀 Next Steps

After completing this checklist:

1. [ ] Apply permissions to other API endpoints
2. [ ] Customize permissions as needed
3. [ ] Train your staff
4. [ ] Monitor activity logs
5. [ ] Regular security audits

---

**Setup Time:** ~60 minutes  
**Difficulty:** Medium  
**Status:** ✅ Complete

**Happy coding! 🎉**
