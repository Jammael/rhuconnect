# Security Notes

- Rotate `APP_KEY` and `RHU_ADMIN_PASSWORD` from any `.env` file that has been shared outside the local environment before deploying to production. Generate a new `APP_KEY` with `php artisan key:generate`, and set a new administrator password.
