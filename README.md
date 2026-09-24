Feira Moderna – Online Household Items Selling System

Feira Moderna is a dynamic e-commerce website designed for selling household items online. The system provides a simple and user-friendly platform where customers can browse products, interact with the store, and place orders.

Features

User-friendly e-commerce interface

Household product listing and management

Product details and browsing

User interaction

Basic shopping and order processing

Dynamic content powered by PHP

Responsive frontend design

Database integration

Email functionality using PHPMailer

Technologies Used

HTML5 – Website structure

CSS3 – Styling and responsive design

JavaScript – Client-side interactions

PHP – Backend development

MySQL – Database management

PHPMailer – Email functionality

XAMPP – Local development environment

Project Structure
feira-moderna-ecommerce-system/
│
├── css/                 # Stylesheets
├── js/                  # JavaScript files
├── images/              # Product and website images
├── files/               # Supporting project files
├── vendor/              # Composer dependencies (not committed)
├── *.php                # PHP application files
├── composer.json        # PHP dependencies
└── README.md            # Project documentation

Requirements

Before running the project locally, make sure you have:

PHP 8.x or compatible version

MySQL

Apache

XAMPP

Composer

A modern web browser

Installation
1. Clone the repository
git clone https://github.com/Hillary-kithinji/feira-moderna-ecommerce-system-.git

2. Move the project to XAMPP

Place the project inside:

C:\xampp\htdocs\


The final location should be similar to:

C:\xampp\htdocs\feira-moderna-ecommerce-system-

3. Start XAMPP

Open XAMPP Control Panel and start:

Apache

MySQL

4. Install PHP dependencies

Open a terminal inside the project directory and run:

composer install

5. Configure the database

Create a MySQL database using phpMyAdmin and import the project's database SQL file if one is provided.

Update your database configuration with your local credentials.

Example:

$host = "localhost";
$username = "root";
$password = "";
$database = "feira_moderna";

6. Configure email

If the project uses PHPMailer, configure your SMTP credentials.

Do not commit passwords, API keys, SMTP credentials, or other secrets to GitHub.

Use environment variables or a local configuration file that is included in .gitignore.

7. Run the project

Open your browser and visit:

http://localhost/feira-moderna-ecommerce-system-/

Development

This project was developed using a local XAMPP environment and is intended for learning, development, and demonstration purposes.

Future Improvements

Possible future enhancements include:

Online payment integration

Customer account management

Advanced order tracking

Product search and filtering

Shopping cart improvements

Admin dashboard

Inventory management

Customer reviews and ratings

Improved security and authentication

Deployment to a production server

Author

Hillary Kithinji

GitHub:
https://github.com/Hillary-kithinji

License

This project is intended for educational and portfolio purposes.
