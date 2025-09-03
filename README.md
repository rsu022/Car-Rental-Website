# Car Rental System

A comprehensive car rental management system with user and admin dashboards, car listings, booking system, and recommendation engine.

## Features

- User Registration and Authentication
- Admin and User Dashboards
- Car Listing and Search
- Booking Management
- Collaborative and Content-Based Filtering for Recommendations
- Responsive Design with Bootstrap 5
- Secure Database Operations with PDO

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Composer (for dependencies)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/car-rental-system.git
cd car-rental-system
```

2. Create a MySQL database and import the schema:
```bash
mysql -u your_username -p your_database_name < database.sql
```

3. Configure the database connection:
Edit `config/database.php` with your database credentials:
```php
private $host = "localhost";
private $db_name = "your_database_name";
private $username = "your_username";
private $password = "your_password";
```

4. Set up the web server:
- Point your web server's document root to the project directory
- Ensure the `assets` directory is writable for file uploads

5. Access the application:
Open your web browser and navigate to:
```
http://localhost/car-rental-system
```

## Directory Structure

```
car-rental-system/
├── admin/              # Admin dashboard and management
├── assets/            # Static files (CSS, JS, images)
│   ├── css/
│   └── js/
├── config/            # Configuration files
├── includes/          # Reusable PHP components
├── index.php          # Home page
├── cars.php           # Car listing page
├── car-details.php    # Car details and booking
├── login.php          # User login
├── register.php       # User registration
├── dashboard.php      # User dashboard
├── database.sql       # Database schema
└── README.md          # This file
```

## Usage

### User Features
- Register and login to the system
- Browse available cars
- Search and filter cars
- Book cars for specific dates
- View booking history
- Update profile information

### Admin Features
- Manage cars (add, edit, delete)
- Manage users
- Manage bookings
- View system statistics
- Generate reports

## Security Features

- Password hashing
- Prepared statements for database queries
- Input validation and sanitization
- Session management
- Role-based access control

## Recommendation System

The system uses two types of filtering for recommendations:

1. Collaborative Filtering:
   - Based on user preferences and behavior
   - Recommends cars liked by similar users

2. Content-Based Filtering:
   - Based on car features and attributes
   - Recommends similar cars based on current selection

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, email support@carrental.com or create an issue in the repository. 
# Car-Rental-Website
Project made using HTML, CSS, JS AND PHP with recommendation algorithm.
