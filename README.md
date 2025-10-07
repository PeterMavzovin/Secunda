docker exec -it laravel_app php artisan key:generate
docker exec -it laravel_app php artisan migrate
docker exec -it laravel_app php artisan l5-swagger:generate
