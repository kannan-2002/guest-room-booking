# Guest Room Booking Application

## Features

### House Owners:
- Add, edit, and delete rooms.
- Manage room details: name, description, size, beds, amenities, rent, booking period.
- Upload room photos.
- View all rooms on the dashboard.

### Customers:
- Browse available rooms.
- View room details and photos.
- Book rooms for available dates.

## Technologies
- PHP, MySQL for backend.
- HTML, CSS for frontend.
- XAMPP for local development.

## Setup

1. **Clone the repo**:  
   `git clone https://github.com/kannan-2002/guest-room-booking.git`
   
2. Database:  
   Create a MySQL database and import `guest_room_booking.sql`.

3. Update DB credentials in `database.php`:
   ```php
   $servername = "localhost";
   $username = "root";
   $password = "";
   $dbname = "guest_room_booking";
   ```

4. Run on localhost using XAMPP.

## Usage

- House Owners: Manage rooms via the dashboard.
- Customers: Browse and book rooms.

--- 

