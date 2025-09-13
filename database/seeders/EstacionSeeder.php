<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Estacion;
use Carbon\Carbon;

class EstacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $estaciones = [
            // Estaciones de Carga
            [
                'nombre' => 'EcoBici Central',
                'descripcion' => 'Estación principal de carga ubicada en el centro de la ciudad. Cuenta con todas las facilidades para usuarios.',
                'ubicacion' => 'Centro Histórico',
                'latitud' => 14.6349,
                'longitud' => -90.5069,
                'tipo_estacion' => 'carga',
                'capacidad_total' => 20,
                'capacidad_disponible' => 15,
                'estado' => 'activa',
                'direccion' => '6ta Avenida 12-23, Zona 1, Ciudad de Guatemala',
                'telefono' => '2234-5678',
                'observaciones' => 'Estación con mayor flujo de usuarios. Mantenimiento cada 15 días.'
            ],
            [
                'nombre' => 'Plaza de Armas',
                'descripcion' => 'Estación de carga cerca de la plaza principal con fácil acceso peatonal.',
                'ubicacion' => 'Plaza Central',
                'latitud' => 14.6395,
                'longitud' => -90.5130,
                'tipo_estacion' => 'carga',
                'capacidad_total' => 15,
                'capacidad_disponible' => 12,
                'estado' => 'activa',
                'direccion' => 'Plaza de Armas, frente a la Catedral',
                'telefono' => '2234-5679',
                'observaciones' => 'Zona turística con alta demanda los fines de semana.'
            ],
            [
                'nombre' => 'Universidad San Carlos',
                'descripcion' => 'Estación de carga estratégicamente ubicada para estudiantes universitarios.',
                'ubicacion' => 'Ciudad Universitaria',
                'latitud' => 14.5906,
                'longitud' => -90.5564,
                'tipo_estacion' => 'carga',
                'capacidad_total' => 25,
                'capacidad_disponible' => 18,
                'estado' => 'activa',
                'direccion' => 'Entrada principal USAC, Ciudad Universitaria',
                'telefono' => '2234-5680',
                'observaciones' => 'Mayor demanda en horarios de clases.'
            ],
            [
                'nombre' => 'Terminal de Buses',
                'descripcion' => 'Estación de carga en la terminal principal para conexiones interurbanas.',
                'ubicacion' => 'Zona 4',
                'latitud' => 14.6157,
                'longitud' => -90.5264,
                'tipo_estacion' => 'carga',
                'capacidad_total' => 30,
                'capacidad_disponible' => 20,
                'estado' => 'activa',
                'direccion' => 'Terminal de Buses Zona 4, 7ma Avenida',
                'telefono' => '2234-5681',
                'observaciones' => 'Conexión importante con transporte público.'
            ],
            [
                'nombre' => 'Mercado Central',
                'descripcion' => 'Estación de carga cerca del mercado para facilitar compras.',
                'ubicacion' => 'Mercado Central',
                'latitud' => 14.6389,
                'longitud' => -90.5115,
                'tipo_estacion' => 'carga',
                'capacidad_total' => 18,
                'capacidad_disponible' => 10,
                'estado' => 'mantenimiento',
                'direccion' => '8va Avenida y 6ta Calle, Zona 1',
                'telefono' => '2234-5682',
                'observaciones' => 'En mantenimiento por renovación de equipos.'
            ],

            // Estaciones de Descanso
            [
                'nombre' => 'Parque La Libertad',
                'descripcion' => 'Área de descanso con bancas y sombra natural para usuarios.',
                'ubicacion' => 'Parque La Libertad',
                'latitud' => 14.6278,
                'longitud' => -90.5189,
                'tipo_estacion' => 'descanso',
                'capacidad_total' => 10,
                'capacidad_disponible' => 8,
                'estado' => 'activa',
                'direccion' => 'Parque La Libertad, 12 Calle Zona 1',
                'telefono' => '2234-5683',
                'observaciones' => 'Área verde con servicios básicos.'
            ],
            [
                'nombre' => 'Mirador del Valle',
                'descripcion' => 'Estación de descanso con vista panorámica de la ciudad.',
                'ubicacion' => 'Zona 10',
                'latitud' => 14.5997,
                'longitud' => -90.5145,
                'tipo_estacion' => 'descanso',
                'capacidad_total' => 12,
                'capacidad_disponible' => 9,
                'estado' => 'activa',
                'direccion' => 'Torre del Reformador, Zona 10',
                'telefono' => '2234-5684',
                'observaciones' => 'Punto turístico con excelente vista.'
            ],
            [
                'nombre' => 'Jardín Botánico',
                'descripcion' => 'Estación de descanso en ambiente natural y tranquilo.',
                'ubicacion' => 'Zona 10',
                'latitud' => 14.5889,
                'longitud' => -90.5234,
                'tipo_estacion' => 'descanso',
                'capacidad_total' => 8,
                'capacidad_disponible' => 6,
                'estado' => 'activa',
                'direccion' => 'Jardín Botánico, Avenida La Reforma',
                'telefono' => '2234-5685',
                'observaciones' => 'Ambiente natural ideal para el descanso.'
            ],
            [
                'nombre' => 'Lago de Amatitlán',
                'descripcion' => 'Estación de descanso junto al lago con actividades recreativas.',
                'ubicacion' => 'Amatitlán',
                'latitud' => 14.4833,
                'longitud' => -90.6167,
                'tipo_estacion' => 'descanso',
                'capacidad_total' => 15,
                'capacidad_disponible' => 12,
                'estado' => 'activa',
                'direccion' => 'Malecón de Amatitlán, frente al lago',
                'telefono' => '2234-5686',
                'observaciones' => 'Destino turístico popular los fines de semana.'
            ],
            [
                'nombre' => 'Parque Ecológico',
                'descripcion' => 'Área de descanso en zona verde protegida.',
                'ubicacion' => 'Zona 13',
                'latitud' => 14.5756,
                'longitud' => -90.5389,
                'tipo_estacion' => 'descanso',
                'capacidad_total' => 10,
                'capacidad_disponible' => 0,
                'estado' => 'inactiva',
                'direccion' => 'Parque Ecológico Zona 13, Aurora I',
                'telefono' => '2234-5687',
                'observaciones' => 'Temporalmente cerrada por mantenimiento mayor.'
            ],

            // Estaciones de Selección
            [
                'nombre' => 'Centro Comercial Pradera',
                'descripcion' => 'Estación de selección en centro comercial con múltiples opciones.',
                'ubicacion' => 'Zona 10',
                'latitud' => 14.6021,
                'longitud' => -90.5098,
                'tipo_estacion' => 'seleccion',
                'capacidad_total' => 35,
                'capacidad_disponible' => 28,
                'estado' => 'activa',
                'direccion' => 'C.C. Pradera Concepción, 13 Calle Zona 10',
                'telefono' => '2234-5688',
                'observaciones' => 'Mayor variedad de bicicletas disponibles.'
            ],
            [
                'nombre' => 'Aeropuerto La Aurora',
                'descripcion' => 'Estación de selección para viajeros con diferentes tipos de bicicletas.',
                'ubicacion' => 'Zona 13',
                'latitud' => 14.5833,
                'longitud' => -90.5275,
                'tipo_estacion' => 'seleccion',
                'capacidad_total' => 40,
                'capacidad_disponible' => 35,
                'estado' => 'activa',
                'direccion' => 'Aeropuerto Internacional La Aurora, Terminal 1',
                'telefono' => '2234-5689',
                'observaciones' => 'Servicio 24 horas para viajeros.'
            ],
            [
                'nombre' => 'Plaza Fontabella',
                'descripcion' => 'Estación de selección en complejo comercial y empresarial.',
                'ubicacion' => 'Zona 10',
                'latitud' => 14.5944,
                'longitud' => -90.5056,
                'tipo_estacion' => 'seleccion',
                'capacidad_total' => 22,
                'capacidad_disponible' => 16,
                'estado' => 'activa',
                'direccion' => 'Plaza Fontabella, Boulevard Liberación',
                'telefono' => '2234-5690',
                'observaciones' => 'Alta demanda ejecutiva en horarios de oficina.'
            ],
            [
                'nombre' => 'Campus Rafael Landívar',
                'descripcion' => 'Estación de selección universitaria con bicicletas especializadas.',
                'ubicacion' => 'Vista Hermosa',
                'latitud' => 14.6056,
                'longitud' => -90.4889,
                'tipo_estacion' => 'seleccion',
                'capacidad_total' => 28,
                'capacidad_disponible' => 20,
                'estado' => 'activa',
                'direccion' => 'Universidad Rafael Landívar, Campus Central',
                'telefono' => '2234-5691',
                'observaciones' => 'Enfocada en estudiantes y personal académico.'
            ],
            [
                'nombre' => 'Hospital San Juan de Dios',
                'descripcion' => 'Estación de selección cerca del hospital principal.',
                'ubicacion' => 'Zona 1',
                'latitud' => 14.6445,
                'longitud' => -90.5156,
                'tipo_estacion' => 'seleccion',
                'capacidad_total' => 20,
                'capacidad_disponible' => 14,
                'estado' => 'activa',
                'direccion' => 'Hospital San Juan de Dios, 1ra Avenida',
                'telefono' => '2234-5692',
                'observaciones' => 'Servicio prioritario para personal médico.'
            ],

            // Estaciones adicionales para mayor diversidad
            [
                'nombre' => 'Estadio Mateo Flores',
                'descripcion' => 'Estación de carga para eventos deportivos y recreativos.',
                'ubicacion' => 'Ciudad de los Deportes',
                'latitud' => 14.6178,
                'longitud' => -90.5445,
                'tipo_estacion' => 'carga',
                'capacidad_total' => 25,
                'capacidad_disponible' => 22,
                'estado' => 'activa',
                'direccion' => 'Estadio Nacional Mateo Flores, Zona 5',
                'telefono' => '2234-5693',
                'observaciones' => 'Aumenta capacidad durante eventos deportivos.'
            ],
            [
                'nombre' => 'Cementerio General',
                'descripcion' => 'Estación de descanso en área histórica y cultural.',
                'ubicacion' => 'Zona 3',
                'latitud' => 14.6234,
                'longitud' => -90.5356,
                'tipo_estacion' => 'descanso',
                'capacidad_total' => 8,
                'capacidad_disponible' => 5,
                'estado' => 'activa',
                'direccion' => 'Cementerio General, 3ra Avenida Zona 3',
                'telefono' => '2234-5694',
                'observaciones' => 'Punto de interés histórico y cultural.'
            ],
            [
                'nombre' => 'Centro Cívico',
                'descripcion' => 'Estación de selección en el corazón administrativo.',
                'ubicacion' => 'Centro Cívico',
                'latitud' => 14.6389,
                'longitud' => -90.5203,
                'tipo_estacion' => 'seleccion',
                'capacidad_total' => 30,
                'capacidad_disponible' => 25,
                'estado' => 'activa',
                'direccion' => 'Centro Cívico, Palacio Nacional',
                'telefono' => '2234-5695',
                'observaciones' => 'Servicio para funcionarios y ciudadanos.'
            ],
            [
                'nombre' => 'Hipódromo del Norte',
                'descripcion' => 'Estación de carga para actividades recreativas.',
                'ubicacion' => 'Zona 7',
                'latitud' => 14.6556,
                'longitud' => -90.5445,
                'tipo_estacion' => 'carga',
                'capacidad_total' => 15,
                'capacidad_disponible' => 10,
                'estado' => 'mantenimiento',
                'direccion' => 'Hipódromo del Norte, 7ma Avenida Zona 7',
                'telefono' => '2234-5696',
                'observaciones' => 'Mantenimiento preventivo programado.'
            ],
            [
                'nombre' => 'Parque Minerva',
                'descripcion' => 'Estación de descanso en parque recreativo familiar.',
                'ubicacion' => 'Zona 11',
                'latitud' => 14.6167,
                'longitud' => -90.5667,
                'tipo_estacion' => 'descanso',
                'capacidad_total' => 12,
                'capacidad_disponible' => 8,
                'estado' => 'activa',
                'direccion' => 'Parque Minerva, 37 Avenida Zona 11',
                'telefono' => '2234-5697',
                'observaciones' => 'Popular para actividades familiares los domingos.'
            ]
        ];

        foreach ($estaciones as $estacionData) {
            Estacion::create(array_merge($estacionData, [
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]));
        }

        $this->command->info('Se han creado ' . count($estaciones) . ' estaciones exitosamente.');
    }
}
