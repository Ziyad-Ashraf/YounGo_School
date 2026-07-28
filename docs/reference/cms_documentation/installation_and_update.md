# ACADEMY
## Course Based Learning Management System

# Installation & Update Guide
*Copyright 2018 Creativeitem. All rights reserved.*

---

## Installation

Please follow the below steps to complete the installation process.

* Upload the downloaded zip file from CodeCanyon to your server.
* You can upload anywhere inside your `public_html` folder or any sub-folder you want. Just keep in mind the directory where you have uploaded it.
* Unzip the file.
* Go to your preferred web browser and type the url where you have unzipped the file. For example - if you have a domain example.com and you have unzipped the files inside a folder ‘academy’, the url will be `example.com/academy`.
* After you have entered the url on your browser will see the screen below.
* This is the first step of the installation. Before starting the installation process, you will need to have CodeCanyon purchase code, the database name, database username, database password and database host. You can get the purchase code from your purchase information on codecanyon, for having the database information, you will need to create a new database on your server. You will also need to make sure that the files in `/application/config/database.php` and `/application/config/routes.php` have write permission. You should also check if php curl is enabled on your server or not.
* After you hit the ‘Start Installation Process’ button you will see the screen below.
* This screen checks if the required files have the write permission and curl is enabled or not. If these are not enabled, you will face issues in the installation process. So make sure that all the three points on that screen have a green check mark. If everything is fine and you hit the ‘Continue’ button will be presented with this screen.
* Here you will need to insert your purchase code that you have got from CodeCanyon and hit the ‘Continue’ button which will lead you to the screen below.
* Here you will need to insert your previously created database credentials correctly. The installer will check if the information are correct after you hit the ‘Continue’ button and if everything is fine, you will be directed to the page below.
* Now all you have to do is hit the ‘Install’ button which will automatically import the database of the application to your created database. Please wait while the import operation is being done. This may take a while according to your server performance.
* After the installer has successfully imported the database, you will get the following page.
* Fill up the informations required and hit the button ‘Set me up’. This will save your school name and administrator login credentials which will be required later for logging in into the application and will present the following page.
* Now hit the ‘Log In’ button which will redirect you to the backend of the application where you will be able to login as an administrator using the email and password you have entered in the previous step.
* Please make sure to go through all the steps chronologically. Otherwise the installation might fail and you will face issues running the application. For any help, contact Creativeitem Support Center.

## Product Update Instructions

* If you are already running the application, please go through the file `update_instructions.txt` which can be found within the downloaded copy of the product in ‘updater’ folder. All the necessary information required to successfully update the product will be there.
