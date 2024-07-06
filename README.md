composer require ggg3/moviechill-core -w

php artisan MovieChill:install

use MovieChill\Core\Models\User as MovieChillUser;

php artisan MovieChill:user

Xóa trong routes/web.php
    Route::get('/', function () {
      return view('welcome');
    });
 php artisan optimize:clear
