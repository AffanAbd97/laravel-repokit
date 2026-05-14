# Laravel RepoKit
> Clean architecture scaffolding for Laravel (Repository + Service layers)

![PHP](https://img.shields.io/badge/PHP-8.1+-blue)
![Laravel](https://img.shields.io/badge/Laravel-10%2B-red)
![License](https://img.shields.io/badge/license-MIT-green)

A Laravel package to scaffold Repository and Service layers, helping enforce clean architecture, improve maintainability, and reduce boilerplate in backend applications.

---

## Why?

In many Laravel projects, business logic and data access are tightly coupled, making the code harder to maintain, scale, and test.

Laravel RepoKit helps you:
- Separate concerns using the Repository and Service patterns
- Maintain a clean and scalable architecture
- Reduce repetitive boilerplate code
- Speed up development with automated scaffolding

This also helps you standardize your application structure across teams and projects.

---

## Key Features

- Repository & Service scaffolding
- Query Builder and Eloquent support
- Automatic interface binding
- Clean architecture enforcement
- Consistent project structure

---

## Architecture Overview

Controller → Service → Repository → Database

- **Controller** handles HTTP requests
- **Service** contains business logic
- **Repository** handles data access

---

## Auto-Binding

All generated interfaces are automatically bound to their implementations in `AppServiceProvider`.

No manual dependency injection setup is required.

---

## Requirements

- PHP ^8.1
- Laravel 10.x, 11.x, or 12.x

---

## Installation

Install the package via Composer:

```bash
composer require sazl/laravel-repokit
```

The service providers will be auto-discovered by Laravel.

---

## Usage

### Repositories

#### Basic Repository (Query Builder)

```bash
php artisan make:repository User
```

This creates:
- `app/Repositories/Contracts/UserRepositoryInterface.php`
- `app/Repositories/Databases/UserRepository.php`

The generated repository uses Laravel's Query Builder (`DB` facade) with a raw query builder approach.

#### Repository with Eloquent Model

```bash
php artisan make:repository User --model=User
```

Generates the same structure, but uses Eloquent model injection instead of Query Builder. The model is resolved as `App\Models\User` unless a fully-qualified class name is provided.

---

### Services

#### Service with Repository Injection

```bash
php artisan make:service User
```

This creates:
- `app/Services/Contracts/UserServiceInterface.php`
- `app/Services/UserService.php`

By default the service is wired to the repository that matches the service name (`UserRepositoryInterface`). Use `--repository` to point to a different one:

```bash
php artisan make:service Order --repository=User
```

#### Empty Service (no pre-built methods)

```bash
php artisan make:service User --empty
```

or the short flag:

```bash
php artisan make:service User -e
```

Generates the same file structure but with an empty interface and a minimal service class, useful when you want to define your own contract from scratch.

---


## Generated File Structure

```
app/
├── Repositories/
│   ├── Contracts/
│   │   └── {Name}RepositoryInterface.php
│   └── Databases/
│       └── {Name}Repository.php
└── Services/
    ├── Contracts/
    │   └── {Name}ServiceInterface.php
    └── {Name}Service.php
```

---

## Example Output

### Repository (Query Builder)

```bash
php artisan make:repository User
```

**Interface** (`app/Repositories/Contracts/UserRepositoryInterface.php`):

```php
interface UserRepositoryInterface
{
    public function all();
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
```

**Implementation** (`app/Repositories/Databases/UserRepository.php`):

```php
class UserRepository implements UserRepositoryInterface
{
    protected $connection = 'mysql';
    protected $table = 'users';

    protected function query()
    {
        return DB::connection($this->connection)->table($this->table);
    }

    public function all() { return $this->query()->get(); }
    public function find($id) { return $this->query()->where('id', $id)->first(); }
    public function create(array $data) { return $this->query()->insertGetId($data); }
    public function update($id, array $data) { return $this->query()->where('id', $id)->update($data); }
    public function delete($id) { return $this->query()->where('id', $id)->delete(); }
}
```

---

### Repository (Eloquent Model)

```bash
php artisan make:repository User --model=User
```

**Implementation** (`app/Repositories/Databases/UserRepository.php`):

```php
class UserRepository implements UserRepositoryInterface
{
    public function __construct(protected User $model) {}

    public function all() { return $this->model->all(); }
    public function find($id) { return $this->model->find($id); }
    public function create(array $data) { return $this->model->create($data); }
    public function update($id, array $data) { ... }
    public function delete($id) { ... }
}
```

---

### Service

```bash
php artisan make:service User
```

**Interface** (`app/Services/Contracts/UserServiceInterface.php`):

```php
interface UserServiceInterface
{
    public function getAll();
    public function getById($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
}
```

**Implementation** (`app/Services/UserService.php`):

```php
class UserService implements UserServiceInterface
{
    public function __construct(
        protected UserRepositoryInterface $repository
    ) {}

    public function getAll() { return $this->repository->all(); }
    public function getById($id) { return $this->repository->find($id); }
    public function create(array $data) { return $this->repository->create($data); }
    public function update($id, array $data) { return $this->repository->update($id, $data); }
    public function delete($id) { return $this->repository->delete($id); }
}
```

---

## Command Reference

| Command | Options | Description |
|---|---|---|
| `make:repository {name}` | `--model` / `-M` | Generate a repository. Optionally inject an Eloquent model. |
| `make:service {name}` | `--repository` / `-R`, `--empty` / `-e` | Generate a service. Optionally target a specific repository or generate an empty scaffold. |

---

## Use Case

This package is ideal for:
- Medium to large-scale Laravel applications
- Teams enforcing clean architecture practices
- Developers who want consistent repository and service structure
- Projects requiring better separation of concerns

---

## Background

This package is inspired by real-world backend development, where maintaining a clear separation between business logic and data access is essential for long-term scalability and maintainability.

---

## Clone and Test Locally

### 1. Clone the Repository

```bash
git clone https://github.com/sazl/laravel-repokit.git
cd laravel-repokit
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Run Tests

This package uses Orchestra Testbench:

```bash
./vendor/bin/phpunit
```

---

### 4. Test in a Laravel Project (Local Development)

To use this package in a local Laravel project without publishing to Packagist or pushing to Git, add a path repository to your Laravel app's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../laravel-repokit"
        }
    ]
}
```

> The `url` is relative to your Laravel app's root. Adjust it to match where you cloned this package.

Then require it using the `@dev` stability flag (since the package has no version tag defined):

```bash
composer require sazl/laravel-repokit:@dev
```

Composer will symlink the package directly into your `vendor` folder, so any changes you make to the package are reflected immediately — no reinstall needed.

Laravel will auto-discover the service providers via the `extra.laravel.providers` entry in `composer.json`.

---

## Testing

This package is tested using Orchestra Testbench to ensure compatibility with Laravel applications.

---

## License

MIT
