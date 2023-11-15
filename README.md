<img align="left" src="https://github.com/BlackyDrum/attestation-management-system/assets/111639941/27316b55-87b6-417c-8ee0-65441b4dabfb" />

<br />

<img src="https://github.com/BlackyDrum/attestation-management-system/assets/111639941/3d8807dd-d4cd-4b25-a63f-2ffa4adfa747"></a><br /><br />

**Create, track and manage attestations efficiently**

<br />

[![Generic badge](https://img.shields.io/badge/Status-Finished-green.svg)](https://shields.io/) [![Generic badge](https://img.shields.io/badge/License-MIT-<COLOR>.svg)](https://shields.io/) 
 
<br />

<img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white"> <img src="https://img.shields.io/badge/Vue.js-35495E?style=for-the-badge&logo=vuedotjs&logoColor=4FC08D"> <img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white"> <img src="https://img.shields.io/badge/PostgreSQL-316192?style=for-the-badge&logo=postgresql&logoColor=white">
<img src="https://img.shields.io/badge/MySQL-005C84?style=for-the-badge&logo=mysql&logoColor=white"> <img src="https://img.shields.io/badge/redis-%23DD0031.svg?&style=for-the-badge&logo=redis&logoColor=white">



<p>
The Attestation Management System is a web application built using Laravel and Vue.js, designed specifically for universities to streamline the process of tracking student tasks and providing attestation. This system aims to simplify the workflow between professors and students, making it easier to monitor the progress and completion of various tasks assigned to students.
</p>

![example](https://github.com/BlackyDrum/attestation-management-system/assets/111639941/96ed7358-b649-401d-8bbf-caf7fb6fa0fd)

## Requirements
<ul>
    <li>PHP: ^8.1</li>
    <li>Node: ^18.12</li>
    <li>Composer: ^2.5</li>
</ul>

## Installation
**Follow these steps to get the Attestation Management System up and running on your local machine:**
1. **Clone the repository:**
```
$ git clone https://github.com/BlackyDrum/attestation-management-system.git
```
2. **Navigate to the project directory:**
```
$ cd attestation-management-system
```
3. **Install the dependencies:**
```
$ composer install
```
4. **Create a copy of the .env.example file and rename it to .env. Update the necessary configuration values such as the Database credentials (PostgreSQL + Redis OR MySQL + Redis) and Pusher credentials:**
```
$ cp .env.example .env
```
5. **Generate an application key:**
```
$ php artisan key:generate
```
6. **Run the database migrations:**
```
$ php artisan migrate
```
7. **Install JavaScript dependencies:**
```
$ npm install
```
8. **Build the assets:**
```
$ npm run dev
```
9. **Seed the database with important data. You can customize the seeder located in '/database/seeders/ImportantDataSeeder':**
```
$ php artisan db:seed --class=ImportantDataSeeder
```
10. **Start the development server:**
```
$ php artisan serve
```
11. **Start the websocket server:**
```
$ php artisan websockets:serve
```
12. **(Optional) You can populate the application with sample data to experience a demonstration version of the app:**
```
$ php artisan db:seed --class=DemoSeeder
```
13. **Start the Redis server.**
14. **Visit http://localhost:8000 in your web browser to access the application.**


## Manual User Creation for Full Access
<p>In order to have full access to the Attestation Management System, you need to manually add a user and set the admin flag to true. You can use the following command:</p>

```sql
INSERT INTO users(matriculation_number,name,email,password,admin,role_id) VALUES (0,'Admin','admin@example.com','$2y$10$7yXSbuH7.wseW.r8ob9ULO1rM7ORxh9n0xp014DCwqOiRct2s1JTm',true,1);
```
**Please note that the password used in this example is "default." It's crucial to change the password to something secure and unique once you gain access to the system.**
