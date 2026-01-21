# SkillSwap - A Peer Learning and Mentoring Platform

SkillSwap is a peer learning and mentoring platform started in 2026. SkillSwap connects learners and mentors through skill offerings, booking requests, learning sessions, and feedback-based trust building. A learner can browse skill offerings created by mentors, request a booked session, join and complete skill learning sessions, and leave a review after completion. A mentor can create skill offerings within selected skill categories, handle booking requests (accept/reject), view ongoing sessions, edit skill offerings anytime, and view ratings and user feedback from completed sessions. Both learners and mentors can view their profiles, modify profile information, and delete their accounts.


## How to Run the Project

1. Download the ZIP or clone this GitHub repository.

2. Move the project folder to: C:\xampp\htdocs\

3. Open `index.php` inside the `htdocs` folder and update this line: ```php header(Location: .$uri./skillswaps);

4. Open XAMPP and start Apache and MySQL, then click Admin on both.

5. In phpMyAdmin: http://localhost/phpmyadmin/ Run the SQL queries from skillswap_db.txt to generate the database.

6. Run the SQL queries from skillswap_data.txt to insert sample data.

SkillSwap Website Now ready for use.
