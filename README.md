Internship Project 2 
Foreign Contract Worker Masterlist (Manual Guide)

1. Login
	(Admin)
	(User)

2. Dashboard Page
	- main dashboard (display count for each category)
	- contract status overview (contract extend/not extend card,line graph comparison)
	- work permit overview (expired,expiring soon,fomema complete/incomplete card)
	- department distribution overview (horizontal bar graph)
	- passport overview (expired,expiring soon card)
	- each card consists of pie chart for details by nationality and department
	- right click graph to save graph as image

3. Passport Information Page
	- view passport expiry (default is 1 year earlier)
	- filter by month and nationality (can be one or both)
	- status (expiring soon, expired, active)
	- renew button (auto update passport by 5 years)
	- every month, email will be sent to admin as a reminder for employee passport that already expired or expiring soon (WHEN THE PAGE IS OPEN).

4. Work Permit Page
	- view permit expiry (default is 2 month earlier from current month)
	- can filter by month and department (can be one or both)
	- update medical checkup status
	- renew button is enable only if both insurance and medical checkup date got a date
	- automatic work permit status expired/expiring soon/active
	- automatic trigger for medical status to mark as incomplete after a year (need new medical checkup once per year)
	- renew button (will update permit by one year)
	- remarks (add comment - press enter to save)

5. Contract Page
	- view contract extend/not by month via work permit expiry
	- filter by month and extend/not extend (count provided)
	- download excel (separate table from same month)

6. Employee Information Page 
	- click any employee to view details
	- can update details and save changes/cancel
	- search employee by name or employee number
	- right click at the employee to delete/(optional)view details
	- scrollable

7. Add Employee Page (icon at the right of Employee Information)
	- access from Employee Information Page navigation bar
	- back to list (go back to employee information)
	- can add photo (must be named as empNo to trigger auto-load)
	- save photo in img/employee_images
	- * in red means required
	- save employee/cancel
	- scrollable

8. List of dropdowns (addEmployee and employeeInfo) :
	- Gender
	- Nationality
	- Department
	- Contract Type

	- Medical Status

