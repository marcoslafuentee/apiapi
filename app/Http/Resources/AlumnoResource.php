<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AlumnoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
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
}
