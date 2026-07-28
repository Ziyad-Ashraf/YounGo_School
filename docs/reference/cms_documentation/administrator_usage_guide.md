# ACADEMY
## Course Based Learning Management System
# Administrator Usage Guide
*Copyright 2021 Creativeitem. All rights reserved*

---

## Index
**Contents for Admin Panel:**

* **Dashboard**
* **Courses**
    * Manage Course
    * Add New Course
    * Course Category
    * Coupons
* **Enrolment**
    * Enrol History
    * Enrol a student
* **Report**
    * Admin Revenue
    * Instructor Revenue
* **Users**
    * Admin
    * Manage Admins
    * Add New Admin
    * Instructors
    * Manage Instructors
    * Add New Instructor
    * Instructor Payout
    * Instructor Settings
    * Students
    * Manage Students
    * Add New Student
* **Message**
* **Applications**
* **Addons**
* **Themes**
* **Settings**
    * System settings
    * Website settings
    * Payment settings
    * Language Settings
    * SMTP Settings
    * About
* **Manage Profile**

---

### 1. Dashboard
a. System summary shown in the home page. Total number of courses, Total number of lessons, Total number of Enrolments, Total number of Students are being shown in the dashboard.

### 2. Categories:
a. Category
b. Add New Category

**How to create Category?**
From the Admin panel navigation menu, go to the Categories. The admin will be able to see a list of categories that admin has created. On the top of the list there is a button named "+Add Category". On clicking that button admin will see a form for creating categories.

**How to create Sub-category?**
For creating sub-categories, Admin can create sub-category for the Category page. He has to select a category to create a subcategory of it. Just click on the Action dropdown menu of that specific category and select the Manage Sub Category option. It will take admin to the Sub-category page. Where admin can see a list of the Sub-categories of that specific category. On the top of the list there is a button named "+Add Sub Category". On clicking that button the admin will see a form for creating a Sub category.

### 3. Courses:

**How to create courses?**
For creating a course, Admin has to create a Category and a Sub-category first. Admin will be able to see all the courses on the Course page. Go to the Courses option from the admin left navigation menu, it will show a list of all created Courses. By selecting the desired category and sub category admin can filter the course list. A form will appear after clicking on the "+Add course" button on the top. Admin can create new courses by giving all the necessary data. While creating a course admin can provide valuable information like meta keyword, meta description for that specific course to make it easy for search engines to find and organize it. What we call Search Engine Optimization or SEO.

**How to add multiple instructors in a single course?**
Adding multiple instructors in a single course can be done in the course edit view. After creating a course go to course edit view and select the tab Basic. There you will find "Add new instructor" field. Type your desired instructors name or email you will find him. Click enter and like this you can add multiple instructors for a single course.

*Figure: COURSE MANAGER - Add new instructor interface*
*Figure: COUPONS management interface*

**How to create Coupon codes?**
In Academy Admin can create coupon codes for students. Student can use this coupon codes while purchasing courses. The total price will be reduced according to the given discount against each coupon code. Admin can add this coupon code from course section.

**How to manage Sections?**
Every course should have at least one section. Admin can select a course for managing sections of it. Just go to the Course page from the admin navigation menu, select a course, click on the Action dropdown menu, choose Sections and Lesson option. A list of created sections for that specific course will appear. Admin can create new sections by simply clicking on "+Add Section" button. Admin can Edit, Delete and Serialize those sections also.

*Figure: Add new lesson interface*

**How to manage Lessons?**
For managing course lessons, the admin can go to the course edit view. The button "+Add Lesson" will appear there. Clicking on that button will show the available types of lessons. Admin will select one and will provide required data for that specific type of lesson.

**What are the available types of lessons?**
Right now, Academy has:
* YouTube video type lesson
* Vimeo video type lesson
* Video file type lesson
* Video url [.mp4] type lesson
* Document [pdf, doc, txt] type lesson
* Image type lesson and
* Iframe type lesson

**Required things you need to add YouTube or Vimeo video lessons.**
For adding YouTube or Vimeo video lessons, you will need their API key first. For YouTube, make sure that DataApi is enabled and allow_url_fopen is enabled in your server. If any of those is not enabled, YouTube video urls will not work how it is supposed to work like, Duration will not be calculated or Course preview will not work etc. Now put those keys inside system settings and you are good to go.

**Things you need to check before adding a Video file lesson?**
Video file type lessons are nothing but video files which you can upload via your computer or other system. But before uploading any video, there are some necessary things you need to check. Check these below points on your server's php configuration.
* Max execution time
* Post max size
* Upload_max_filesize

Below you will find some FAQs about these.

**How to increase Max_execution_time on server?**
The view or availability can differ from server to server. Max_execution_time determines the maximum time it will take to execute a form submission. The straight answer of this question is to update the Max_execution_time on php ini file. The location of the ini file depends on your server. Like on most of the servers you can change it from MultiPHP INI Editor. Click on MultiPHP INI Editor and choose your domain first and then search for Max_execution_time. The default value of Max_execution_time is 30. Which means 30 seconds. Increase it to at least 300 seconds.

**How to increase upload_max_filesize on server?**
The view or availability can differ from server to server. upload_max_filesize determines the maximum size for uploading. You can update the upload_max_filesize from php ini file. The location of the ini file depends on your server. Like, on most of the servers you can change it from MultiPHP INI Editor. Click on MultiPHP INI Editor and choose your domain first and then search for upload_max_filesize. The default value of upload_max_filesize is 32M. Which means 32 Megabyte. The value of upload_max_filesize depends on the videos you are going to upload. Let's say you've updated the upload_max_filesize value to 256M which means 256 Megabyte. After that you will be able to upload videos which are below or equal 256 Megabyte. Bigger than that will not be uploaded.

**How to increase post_max_size on server?**
The view or availability can differ from server to server. post_max_size determines the maximum size for all POST body data of a form. You can update the post_max_size from php ini file. The location of the ini file depends on your server. Like, on most of the servers you can change it from MultiPHP INI Editor. Click on MultiPHP INI Editor and choose your domain first and then search for post_max_size. The default value of post_max_size is 8M. Which means 8 Megabyte. The value of post_max_size depends on the videos you are going to upload. Let's say you've updated the post_max_size value to 260M which means 260 Megabyte. After that you will be able to upload videos which are below 260 Megabyte. Bigger than or equal to that will not be submitted. Because it will be submitted via a form. One last thing, make sure that the value of post_max_size is bigger than upload_max_filesize.

*N.B: Increase the value of those directives otherwise files will not be uploaded. If you have done with the configuration part, Now you can simply provide the Title, Section, Duration and Summary to the lesson add form.*

*Figure: ASSIGN PERMISSION FOR : ROSS GELLER*

### 4. Admins

**How to create Admins and how to provide permission to those admins?**
As root admin, you can create multiple other admins in Academy. But those are not root admins like you. They are kind of sub admins. You can provide permissions to them and they can perform only those actions. Like if you give permission to an admin to access the courses only he can only be able to access the Course feature only. Not others.

### 5. Instructors

**How to create Instructors?**
For adding instructors, go to the instructor menu from the left side navigation bar and select instructor list. You will find all the available instructors there. On the top there is a button called Add instructor. Click the Add instructor button, and it will take you to the instructor registration form. Provide data and press submit for submitting the form.

**How to manage Instructor Payouts?**
Now, As Admin you can manage instructor payouts. Click on the Instructor payout menu from the left sidebar. You will find a tab view inside it. Completed Payout tab will have a list of completed or processed payouts. You can filter with a preferred data range. On the other hand Pending payout list will have all the requested payouts by different instructors.

**How to pay or process a pending payout requested by an instructor?**
If an instructor publishes a course, on every purchase he will get a commission. That commission percentage will be set by admin from the admin settings. Below we've described how to set the commission percentage. If that course got purchased multiple times the instructor's revenue will get increased. Then if an instructor wants to withdraw the amount, he can simply put it with a withdrawal request to the admin. Admin will have it inside Pending Payouts with all other requested payouts. Then admin can simply pay that requested payout via paypal or stripe.

**How to manage Instructor settings?**
Admin can enable or disable the public instructor role from the Instructor settings. If the admin keeps it as Disabled, no user can apply for becoming an Instructor.

**How to set Instructor Commision percentage?**
Go to the Instructor settings and put the instructor revenue percentage there.

### 6. Student:
a. Admin will be able to see all the student list who signed up for. Just go to Student from Admin navigation menu. Admin will be able to create student by himself also. He can also Edit and Delete student from the Student page.

### 7. Enrollment:
a. Admin will be able to see the list of all the enrolled student will appear on Enroll history page. Admin can find this from admin navigation menu to Enroll History.
b. Admin can enroll a student manually. From Enroll A Student option.

### 8. Report:
**a. Admin Revenue:**
i. After every successful course purchasing, Admin will get the entire amount as revenue if he creates the course. And if he is not the creator of that course he will get a predefined amount from that. Admin revenue option will show all of them.

**b. Instructor Revenue:**
i. If an instructor publishes a course, After a successful purchasing Admin will pay him an amount (Which will be calculated based on Instructor settings > Instructor Revenue Percentage). All the payment info with payment status will be shown here.

### 9. Message:
a. Admin can find a Message option in the admin navigation menu. Where he can start or continue a one to one conversation between him and his students.

### 10. Addons:
a. Addon Manager
b. Available Addons

**What is Addon manager?**
Academy LMS now supports addon system for expanding application utilities. We will release different types of addons day by day which will add a new dimension on Academy. For getting addons, you can click on the Available addons option or you can check our website.

### 11. Settings:
a. System Settings
b. Frontend Settings
c. Payment Settings
d. Manage Language

**What is System Settings?**
Settings are the key component of any management system. The flexibility of a system is defined by its easy to handle settings. The "System Settings" will let the Admin to change the basic system settings by editing the required data field. Also it gives a feasibility to change logo, contact information other important settings aspect. On system settings Admin now can provide valuable information like meta keyword, meta description and author name and make it easy for search engines to find and organize it. What we call Search Engine Optimization or SEO.

**What is Frontend Settings?**
The "Frontend Settings" will let the Admin to change the basic settings for Fronted by editing the required data field. Also it gives a feasibility to change the frontend logo, home banner, About, Privacy Policy and Terms and condition other important settings aspects.

**What is Payment Settings?**
The "Payment Settings" will let the Admin change the payment settings like Client ids, Secret keys, public keys of different types of payment gateways. Also it gives a feasibility to change the Test Mode. From payment settings admin can set the currency. Admin can set 3 types of currencies. One for System default currency, another is for PayPal currency and the last one is for Stripe currency. For keeping consistency Admin should keep all the currency the same.

**What is Manage Language?**
The software supports multiple languages. For selecting a language which can be availed from "Settings" section. Click on "Language Settings" option and it will show all the available languages and allows new languages with its "Add Language" tab.
Academy LMS now supports Language switching from the Frontend. On the footer there is a Language switcher for the end users. One thing, When a user switches a language from frontend it will translate the frontend part only, not the entire system. The backend language will still be managed by the Admin himself.
Academy LMS now supports RTL (Right to Left) alignment. Not by default. A new theme has been released for Academy LMS, which will make this application's frontend alignment to RTL.

### 11. Manage Profile
The profile information can be edited by clicking at "Admin Profile image" from the Header. The information can be edited and saved using this "Update Profile".
