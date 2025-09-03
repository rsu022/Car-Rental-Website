```mermaid
graph TD
    %% Level 0 DFD - Context Diagram
    subgraph Level0[Level 0 - Context Diagram]
        User((User))
        Admin((Admin))
        System[Car Rental System]
        User -->|Browse/Book| System
        System -->|Car Listings/Bookings| User
        Admin -->|Manage System| System
        System -->|Reports/Stats| Admin
    end

    %% Level 1 DFD
    subgraph Level1[Level 1 - Main Processes]
        User1((User))
        Admin1((Admin))
        
        %% User Processes
        A1[1.0 User Authentication]
        A2[2.0 Car Management]
        A3[3.0 Booking System]
        A4[4.0 License Verification]
        A5[5.0 Review System]
        
        %% Admin Processes
        B1[6.0 Admin Dashboard]
        B2[7.0 Car Management]
        B3[8.0 User Management]
        B4[9.0 Booking Management]
        
        %% Data Stores
        D1[(User Database)]
        D2[(Car Database)]
        D3[(Booking Database)]
        D4[(License Database)]
        D5[(Review Database)]
        
        %% User Flows
        User1 -->|Login/Register| A1
        A1 -->|Store| D1
        User1 -->|Browse/Search| A2
        A2 -->|Query| D2
        User1 -->|Book Car| A3
        A3 -->|Create| D3
        User1 -->|Upload License| A4
        A4 -->|Store| D4
        User1 -->|Submit Review| A5
        A5 -->|Store| D5
        
        %% Admin Flows
        Admin1 -->|Access| B1
        Admin1 -->|Add/Edit Cars| B2
        B2 -->|Update| D2
        Admin1 -->|Manage Users| B3
        B3 -->|Update| D1
        Admin1 -->|Process Bookings| B4
        B4 -->|Update| D3
    end

    %% Level 2 DFD - Detailed Booking Process
    subgraph Level2[Level 2 - Booking Process Detail]
        User2((User))
        
        %% Booking Sub-processes
        C1[3.1 View Car Details]
        C2[3.2 Check Availability]
        C3[3.3 Enter Booking Details]
        C4[3.4 Upload License]
        C5[3.5 Process Payment]
        C6[3.6 Confirm Booking]
        
        %% Data Stores
        E1[(Car Inventory)]
        E2[(Booking Records)]
        E3[(License Records)]
        
        %% Flows
        User2 -->|Select Car| C1
        C1 -->|View Details| E1
        C1 -->|Check Dates| C2
        C2 -->|Verify| E1
        C2 -->|Enter Info| C3
        C3 -->|Upload| C4
        C4 -->|Store| E3
        C4 -->|Process| C5
        C5 -->|Complete| C6
        C6 -->|Store| E2
    end
```

# Car Rental System - Data Flow Diagram (DFD)

## Level 0 - Context Diagram
The context diagram shows the system as a single process and its interactions with external entities (users and admin).

## Level 1 - Main Processes
This level breaks down the system into major processes based on actual implementation:
1. User Authentication (login.php, register.php)
2. Car Management (cars.php, car-details.php)
3. Booking System (book-car.php)
4. License Verification (upload-license.php)
5. Review System (submit-review.php)
6. Admin Dashboard (dashboard.php)
7. Car Management (add-car.php, edit-car.php)
8. User Management (admin/user management)
9. Booking Management (admin/booking management)

## Level 2 - Booking Process Detail
This level provides a detailed view of the booking process as implemented:
1. View Car Details
2. Check Availability
3. Enter Booking Details
4. Upload License
5. Process Payment
6. Confirm Booking

## Data Stores
- User Database: Stores user information and credentials
- Car Database: Maintains car inventory and details
- Booking Database: Records all booking transactions
- License Database: Stores user license information
- Review Database: Stores user reviews and ratings

## Key Features (Based on Actual Implementation)
- User authentication and registration
- Car browsing and detailed view
- Booking management with license verification
- Review submission system
- Admin dashboard with comprehensive controls
- Car management (add/edit/delete)
- User management
- Booking processing
- License verification system

## Security Features
- Secure user authentication
- License verification process
- Admin access control
- Secure payment processing
- Data validation and sanitization 