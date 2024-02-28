# API

### Primeros pasos
Para comenzar, creamos el proyecto

```bash
laravel new apiFinal
```

Seleccionamos todo default, si quieres un repositorio en git lo seleccionas, y la base de datos mysql.
<br>

#### Creamos modelo
<br>
Utilizamos el comando para crear un modelo de Eloquent llamado "Alumno". La opción --api indica que este modelo se utilizará para una API, y la opción -fm crea un controlador de recursos junto con el modelo. Esto nos ayuda a definir rápidamente la estructura de datos y los controladores necesarios para nuestro modelo.

```bash
cd apiFinal
php artisan make:model Alumno --api -fm  
```


Es importante configurar los archivos .env y docker-compose.yaml

En el archivo .env, configuramos los detalles de la base de datos, incluyendo el nombre de la base de datos (DB_DATABASE), el nombre de usuario (DB_USERNAME), la contraseña (DB_PASSWORD), la contraseña del usuario root (DB_PASSWORD_ROOT), y el puerto para phpMyAdmin (DB_PORT_PHPMYADMIN). Estos detalles son importantes para que Laravel pueda conectarse correctamente a la base de datos.
```php
DB_DATABASE=instituto
DB_USERNAME=alumno
DB_PASSWORD=password
DB_PASSWORD_ROOT=root
DB_PORT_PHPMYADMIN=8080

...

LANG_FAKE ="es_ES"
```

En el archivo docker-compose.yaml, configuramos dos servicios: mysql y phpmyadmin. El servicio mysql proporciona una base de datos MySQL para nuestro proyecto, mientras que el servicio phpmyadmin nos permite administrar la base de datos a través de una interfaz web. Específicamente, configuramos los volúmenes, los puertos y las variables de entorno necesarias para cada servicio.

```php
version: "3.8"
services:
  mysql:
    # image: mysql      Era otra opcion sin el build
    image: mysql

    
    volumes:
      - ./datos:/var/lib/mysql
    ports:
      - ${DB_PORT}:3306
    environment:
      - MYSQL_USER=${DB_USERNAME}
      - MYSQL_PASSWORD=${DB_PASSWORD}
      - MYSQL_DATABASE=${DB_DATABASE}
      - MYSQL_ROOT_PASSWORD=${DB_PASSWORD_ROOT}

    phpmyadmin:
        image: phpmyadmin
        container_name: phpmyadmin  
        ports:
        - ${DB_PORT_PHPMYADMIN}:80
        depends_on:
        - mysql
        environment:
        PMA_ARBITRARY: 1 
        PMA_HOST: mysql
```
El de siempre.

### Lanzar aplicación

```bash
docker compose up -d  
php artisan serve &
```
Si ya hay contenedores levantados los tumbas y los vuelves a levantar

### Poblar base de datos

En database/factories/AlumnoFactory.php
<br>
<br>
Se utiliza para generar datos falsos para los alumnos.
```php
public function definition(): array
{
    return [
    "nombre" =>fake()->name(),
    "direccion" =>fake()->address(),
    "email" =>fake()->email()
    ];
}
```

En database/seeders/DatabaseSeeder.php
<br>
<br>
Definimos un método run donde utilizamos la fábrica para crear 20 registros de alumnos en la base de datos.
```php
public function run(): void
{
    Alumno::factory(20)->create();
}
```
Importando la clase.

Configuramos la localización de Faker para que genere datos en español en el archivo config/app.php estableciendo:
```php
'faker_locale' => 'es_ES',  
```
<br>
Y en database/migrations/2024_02_20_092559_create_alumnos_table.php

Creamos la tabla de alumnos con los campos id, nombre, direccion, email y timestamps.
```php
    Schema::create('nombre_plural', function (Blueprint $table) {
            $table->id();
            $table->string("nombre");
            $table->string("direccion");
            $table->string("email");
            $table->timestamps();
        });
```
<br>

Por últimos vamos a poblarla con los parámetros que le hemos pasado.
```bash
php artisan migrate --seed 
```
### Algunos detalles

Importamos el controlador de Alumno y definimos una ruta de recursos API para los alumnos usando Route::apiResource("alumnos", AlumnoController::class). Esto define las rutas RESTful para el controlador de Alumno, que incluyen rutas para index, show, store, update y destroy.

En routes/api.php escribimos: 

```php
use \App\Http\Controllers\AlumnoController;

Route::apiResource("alumnos",AlumnoController::class);
```
<br>
En app/Http/Controllers/AlumnoController.php

Recuperamos todos los registros de alumnos utilizando Alumno::all() y luego los devolvemos como una respuesta JSON utilizando response()->json($alumnos).
```php
public function index()
{
    $alumnos = Alumno::all();
    return response()->json($alumnos);
}
```
<br>

### Creamos resource y request
```bash 
php artisan make:request AlumnoFormRequest

php artisan make:resource AlumnoResource

php artisan make:resource AlumnoCollection
```
<br>

### AlumnoCollection

En app/Http/Resources/AlumnoCollection.php 

Definimos el método with() para agregar metadatos JSON:API a la respuesta.

Añadimos debajo de la otra función:

```php
public function with(Request $request)
    {
    return[
        "jsonapi" => [
            "version"=>"1.0"
        ]
    ];
}
```

### AlumnoResource

En app/Http/Resources/AlumnoResoruce.php

Definimos el método toArray() para personalizar la salida de los datos del alumno en formato JSON:API. 


```php
public function toArray(Request $request): array{
    return[
        "id"=>$this->id,
        "type" => "Alumno",
        "attributes" => [
            "nombre"=>$this->nombre,
            "direccion"=>$this->direccion,
            "email"=>$this->email,
        ],
        "link"=>url('api/alumnos'.$this->id)
    ];
}
```

Y ahora podemos volver a AlumnoController y definir la función show():

Utilizamos la ruta para importar el recurso de alumno y luego devolvemos una nueva instancia de AlumnoResource que representa al alumno específico.

```php
public function show(Alumno $alumno)
{
    use App\Http\Resources\AlumnoResource;
    return new AlumnoResource($alumno);
} 
```


## Ahora pasamos al manejo de los errores

### No conexión a la db

Vamos a app/Exceptions/Handler.php

Hemos definido un método render para manejar las excepciones de QueryException, que ocurren 
cuando hay un error en una consulta de base de datos. En caso de una excepción de este tipo, 
respondemos con un JSON que contiene lo indicado abajo.

```php
use Illuminate\Database\QueryException;

public function render($request, Throwable $exception)
{
    if ($exception instanceof QueryException) {
        return response()->json([
            'errors' => [ 
                [
                    'status' => '500',
                    'title' => 'Database Error',
                    'detail' => 'Error procesando la respuesta. Inténtelo más tarde.'
                ]
            ]
        ], 500);
    }
    // Si no salta el if
    return parent::render($request, $exception);
}
```
### Creamos el middleware

```bash 
php artisan make:middleware HandleMiddleware
```
<br>

Y ahora vamos a app/Http/Middleware/HandleMiddleware.php

Definimos la lógica en el método handle para verificar si la cabecera accept de la 
solicitud es igual a 'application/vnd.api+json'. Si no lo es, respondemos con un JSON 
que indica que el contenido no es aceptable y un código  406.

```php  
public function handle(Request $request, Closure $next): Response
{
    if ($request->header('accept') != 'application/vnd.api+json') {
        return response()->json([
            "errors"=>[
                "status"=>406,
                "title"=>"Not Accetable",
                "deatails"=>"Content File not specifed"
            ]
        ],406);
    }
    return $next($request);
}
```
<br>

Ahora vamos al kernel para
app/Http/Kernel.php

Esto asegura que el middleware se aplique a todas las solicitudes API.

Dentro de 'api' que esta en $middlewareGroups metemos:
```php
HandleMiddleware::class;
```
Y la importas o escribes su ruta.

<br>

### Usuario no encontrado

Vamos a app/Http/Controllers/AlumnoController.php

Hemos definido el método store donde intentamos obtener datos de la solicitud y 
creamos una instancia de un request específico (NombreFormRequest).
```php
public function store(NombreFormRequest $request)
{
    $datos = $request->input("data.attributes");
}
```
<br>
Ahora vamos a app/Http/Requests/AlumnoRequest.php

Definimos reglas de validación para los datos enviados en la solicitud POST para 
crear un alumno. Estas reglas incluyen que el nombre sea obligatorio y tenga al menos 
5 caracteres, que la dirección sea obligatorio y que el email sea único en la tabla de alumnos.
```php
public function authorize(): bool
{
    return true;
}

public function rules(): array
{
    return [
        "data.attributes.nombre"=>"required|min:5",
        "data.attributes.direccion"=>"required",
        "data.attributes.email"=>"required|email|unique:alumnos"
    ];
}
```

## Postman

Para comprobar que la Api nos devuelve los datos lo primero que hay que hacer es introducir en postman el Header <br>
Los valoren deben ser Key: 'Accept' y Value: 'application/vnd.api+json'
<br>
<br>
Ahora teniendo seleccionado GET ponemos nuestra dirección 'http://localhost:8000/api/alumnos/' y nos devuelve el resultado

<br><br>

### Organizar en artículos 

Vamos a app/Models/Alumno.php

Especificamos qué campos se pueden asignar masivamente. 
Esto nos ayuda a prevenir asignaciones masivas no deseadas y a mantener 
la seguridad de nuestra aplicación.

Ahora dentro de nuestra clase Alumno añadimos:
```php
protected $fillable = ["nombre","direccion","email"];
```
<br>

Ahora vamos a app/http/Controllers/AlumnoController.php
<br>
Añadimos en la función store lo siguiente:
```php
$alumno = new Alumno($datos);
$alumno->save();

return new AlumnoResource($alumno);
```
<br>

Por último volvemos a app/http/Request/AlumnoFormRequest.php

Agregamos reglas de validación para el campo de correo electrónico
para asegurarnos de que sea único en la tabla de alumnos.
```php
"data.attributes.email"=>"required|email|unique:alumnos,email"
```  
Añadimos 'email' al final.

<br><br>

### POST 
Ahora ya podemos hacer un post desde Postman, seleccionamos la opción POST y vamos a 'body', seleccionamos raw y en formato elegimos JSON
<br>
Introducimos esto con los datos que queramos y enviamos
```bash
{
    "data": 
    {
        "type": "alumnos",
        "attributes": 
        {
            "nombre": "Paquito",
            "direccion": "suCasa",
            "email": "a@a.com"
        }
    }
}
```
<br>

### DELETE
Vamos a app/http/Controllers/NombreController.php

Definiremos la función destroy donde buscamos el alumno por su id y lo 
eliminamos si existe, respondiendo con un código de estado HTTP 404 
(Not Found) si el alumno no se encuentra.
```php
public function destroy($id)
    {
        $alumno = Alumno::find($id);
        if (!$alumno) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '404',
                        'title' => 'Resource Not Found',
                        'detail' => 'The requested resource does not exist or could not be found.'
                    ]
                ]
            ], 404);
        }

        $alumno->delete();
        return response()->json(null,204);
    }
}
```
Importante pasarle a la función solo la variable $id sin Alumno

<br>

### UPDATE

Vamos a app/http/Controllers/NombreController.php

Primero validamos los datos de la solicitud según el verbo utilizado (PUT o PATCH), 
luego actualizamos el alumno con los datos proporcionados y respondemos con una nueva 
instancia de AlumnoResource que representa al alumno actualizado.

Definimos la función update:
```php
public function update(Request $request, int $id)
    {
        $alumno = Alumno::find($id);

        if (!$alumno) {
            return response()->json([
                'errors' => [
                    [
                        'status' => '404',
                        'title' => 'Resource Not Found',
                        'detail' => 'The requested resource does not exist or could not be found.'
                    ]
                ]
            ], 404);
        }

        $verbo = $request->method();
        //En función del verbo creo unas reglas de
        // validación u otras
        if ($verbo == "PUT") { //Valido por PUT
            $rules = [
                "data.attributes.nombre" => ["required", "min:5"],
                "data.attributes.direccion" => "required",
                "data.attributes.email" => ["required", "email", Rule::unique("alumnos", "email")->ignore($alumno)]
            ];

        } else { //Valido por PATCH
            if ($request->has("data.attributes.nombre"))
                $rules["data.attributes.nombre"]= ["required", "min:5"];
            if ($request->has("data.attributes.direccion"))
                $rules["data.attributes.direccion"]= ["required"];
            if ($request->has("data.attributes.email"))
                $rules["data.attributes.email"]= ["required", "email", Rule::unique("alumnos", "email")->ignore($alumno)];
        }

        $datos_validados = $request->validate($rules);
        //dump($datos_validados);

        foreach ($datos_validados['data']['attributes'] as $campo=>$valor)
            $datos[$campo] = $valor;

        $alumno->update($request->input("data.attributes"));

        return new AlumnoResource($alumno);
    }
```
<br>

Para que esto funcione en postman tendrás que pasarlo con este formato, usando PATCH
```bash
{
    "data": {
        "type": "Alumnos",
        "attributes": {
                "nombre": "paquita"
        }
    }
}
```
Y con este usando PUT: 
```bash
{
    "data": {
        "type": "alumnos",
        "attributes": {
            "nombre": "PaquitoPrime",
            "direccion": "Nueva dirección23",
            "email": "nuevo@example.com"
        }
    }
}
```
