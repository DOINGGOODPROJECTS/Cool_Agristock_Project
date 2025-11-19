<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
# COOL-AGRISTOCK
# COOL-AGRISTOCK

## Database Overview

The application connects to a MySQL instance using the following environment values:

- `DB_HOST`: 127.0.0.1
- `DB_PORT`: 3306
- `DB_DATABASE`: agristock_db
- `DB_USERNAME`: admin@agristock.com
- `DB_PASSWORD`: Dev5555!

### Table Summary

| Table | Rows | Purpose |
| --- | --- | --- |
| activities | 65 | Login session audit entries per user. |
| billings | 7 | Billing records associated with stock transactions. |
| capacities | 14 | Supported container or packaging types. |
| categories | 7 | Product category definitions. |
| cities | 2 | City reference data. |
| claims | 8 | Customer service and stock-related claims. |
| details | 13 | Line items tying stocks to products and quantities. |
| groups | 9 | User group and role definitions. |
| incidents | 5 | Operational incident tracking. |
| migrations | 4 | Laravel migration housekeeping table. |
| payments | 4 | Recorded payments against billings. |
| products | 47 | Master product catalogue with storage guidance. |
| releases | 1 | Stock release events and logistics details. |
| rottens | 0 | Pending table for spoiled stock records. |
| stocks | 7 | Stock entries stored in facilities. |
| storages | 4 | Storage facilities and capacities. |
| tariffs | 33 | Pricing matrix for storage/container combinations. |
| temperatures | 4 | Temperature monitoring snapshots per storage. |
| users | 6 | Application user accounts and contact data. |

### activities

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| description | varchar(50) | YES |  |  |
| user_id | int | NO | MUL |  |
| login_at | timestamp | NO |  |  |
| logout_at | timestamp | YES |  |  |

### billings

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| ref | varchar(50) | NO |  |  |
| amount | double | NO |  |  |
| discount | double | NO |  |  |
| stock_id | int | NO | MUL |  |
| customer_id | int | NO | MUL |  |
| created_at | timestamp | NO |  |  |
| delayed_at | timestamp | YES |  | DEFAULT_GENERATED |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |

### capacities

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| name | varchar(50) | NO |  |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |

### categories

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| name | varchar(90) | NO |  |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |

### cities

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| name | varchar(50) | NO |  |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |

### claims

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| name | set('ENTREE STOCK','SORTIE STOCK','REQUÊTE DE TRI','CONDITIONNEMENT SPECIAL','CONDITIONNEMENT GENERAL','LIVRAISON AVEC ENCAISSEMENT','LIVRAISON SANS ENCAISSEMENT','RENOUVELLEMENT DUREE STOCK','AUTRES') | NO |  |  |
| message | text | NO |  |  |
| status | set('EN COURS','TRAITEE','NON TRAITEE','DISPUTEE') | NO |  |  |
| customer_id | int | NO | MUL |  |
| storage_id | int | NO | MUL |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |

### details

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| qty | double | NO |  |  |
| container_id | int | NO | MUL |  |
| stock_id | int | NO | MUL |  |
| product_id | int | NO | MUL |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |

### groups

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| name | varchar(50) | NO |  |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |

### incidents

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| type | set('INCIDENT SPECIFIQUE','INCIDENT GENERAL') | NO |  |  |
| description | text | NO |  |  |
| status | set('EN COURS','RESOLU','NON RESOLU') | NO |  |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |

### migrations

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int unsigned | NO | PRI | auto_increment |
| migration | varchar(255) | NO |  |  |
| batch | int | NO |  |  |

### payments

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| location | varchar(150) | NO |  |  |
| amount | double | NO |  |  |
| method | set('CASH','MOBILE MONEY','CREDIT CARD','BANK TRANSFER') | NO |  |  |
| description | text | YES |  |  |
| billing_id | int | NO | MUL |  |
| stock_id | int | NO | MUL |  |
| customer_id | int | NO | MUL |  |
| created_by | int | NO | MUL |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |

### products

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| name | varchar(100) | NO |  |  |
| category_id | int | NO | MUL |  |
| min_expired_at | int | NO |  |  |
| max_expired_at | int | YES |  |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |

### releases

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| before_qty | double | NO |  |  |
| qty | double | NO |  |  |
| after_qty | double | NO |  |  |
| delivery | set('Cool AgriTransport','Tierce','Client') | NO |  |  |
| detail_id | int | NO | MUL |  |
| stock_id | int | NO | MUL |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |

### rottens

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| before_qty | double | NO |  |  |
| qty | double | NO |  |  |
| after_qty | double | NO |  |  |
| detail_id | int | NO | MUL |  |
| stock_id | int | NO | MUL |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |

### stocks

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| ref | varchar(50) | NO |  |  |
| type_storage | set('STOCKAGE SEC','STOCKAGE REFRIGERE') | NO |  |  |
| qty | double | NO |  |  |
| storage_id | int | NO | MUL |  |
| customer_id | int | NO | MUL |  |
| created_by | int | NO |  |  |
| expired_at | int | NO |  |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |

### storages

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| name | varchar(150) | NO |  |  |
| capacity | double | NO |  |  |
| location | varchar(150) | NO |  |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |

### tariffs

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| price | double | NO |  |  |
| min_qty | double | NO |  |  |
| max_qty | double | NO |  |  |
| duration | int | NO |  |  |
| storage_id | int | NO | MUL |  |
| container_id | int | NO | MUL |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |

### temperatures

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| type_storage | set('STOCKAGE A SEC','STOCKAGE REFRIGERE') | NO |  |  |
| session | set('PASSAGE 1','PASSAGE 2','PASSAGE 3') | NO |  |  |
| degree | float | NO |  |  |
| session_time | time | NO |  |  |
| storage_id | int | NO | MUL |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |

### users

| Column | Type | Null | Key | Extra |
| --- | --- | --- | --- | --- |
| id | int | NO | PRI | auto_increment |
| name | varchar(255) | NO |  |  |
| phone | varchar(30) | NO |  |  |
| email | varchar(150) | YES |  |  |
| username | varchar(150) | YES |  |  |
| password | varchar(255) | NO |  |  |
| email_verified_at | timestamp | YES |  |  |
| remember_token | varchar(100) | YES |  |  |
| locale | char(4) | NO |  |  |
| group_id | int | NO | MUL |  |
| created_at | timestamp | NO |  |  |
| updated_at | timestamp | NO |  |  |
| deleted_at | timestamp | YES |  |  |
