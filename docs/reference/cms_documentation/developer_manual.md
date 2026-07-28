# ACADEMY
## Course Based Learning Management System

# Developer Manual
*Copyright 2018 Creativeitem. All rights reserved.*

---

## Source Code Structure:
We followed standard MVC structure within powerful Php Codeigniter framework to develop this project.

### 1.(a) Structure of application directory
* **Controllers:**
    * **Admin.php:** It runs all the functions of admin panel.
    * **Home.php:** All the functionalities of frontend are written in here.
    * **Install.php:** Install.php controller handles the installing process.
    * **Login.php:** The functionalities of processing login system are written in here.
    * **Modal.php:** Modal controller has the functionalities of pop up views.
    * **Updater.php:** We've used Updater.php controller for updating the application.

### 1.(b) Structure of application Directory:
* **Models:**
    * **Crud_Model.php:** We have done all the Create, Read, Update and Delete functionalities on the Crud_model.php file.
    * **User_model.php:** All the database functionalities related to User are done here.
    * **Video_model.php:** Functionalities of fetching data from servers are done here.
    * **Email_model.php:** Email model handles only those functions which are responsible for sending mails.

### 1.(c) Structure of application folder:
* **Views:**
    * **Backend:** Inside views directory there is a couple of folders. Backend has all the views for Admin.
    * **Frontend:** All of the views of frontend site are inside this sub directory.
    * **Install:** Install directory has all the views of installation.

### 2.(a) Structure of Asset directory:
* **Backend:** This directory contains all the CSS, JS and essential plugins of Admin panel.
* **Frontend:** Frontend directory contains all the CSS, JS, essential plugins of Frontend site.
* **Payment:** Payment directory has all the CSS, JS and images for payment views.

### 3.(a) Structure of Uploads directory:
* **Frontend:** Home banner image of frontend site is located here.
* **Thumbnails:** This directory contains all the thumbnails like Course thumbnails and Lesson thumbnails.
* **User image:** All of the users and admin’s profile image is located here.
