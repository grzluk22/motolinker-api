<?php

namespace App\Controller;

use App\Entity\Car;
use App\Repository\CarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;

class CarController extends AbstractController
{

    /**
     * Zwraca samochód pasujący do kryteriów RequestBody
     *
     *
     * @OA\Tag(name="Car")
     * @OA\RequestBody(
     *     request="CarGetRequestBody",
     *     description="Parametry samochodu do wyszukania",
     *     required=false,
     *     @OA\JsonContent(
     *                     example={
     *                              "manufacturer": "Opel",
     *                              "model": "Vectra",
     *                              "type": "C",
     *                              "model_from": "2002-09",
     *                              "model_to": "2004-5",
     *                              "body_type": "Sedan",
     *                              "drive_type": "FWD",
     *                              "displacement_liters": "1655",
     *                              "displacement_cmm": "1655",
     *                              "fuel_type": "Gas",
     *                              "kw": "90",
     *                              "hp": "120",
     *                              "cylinders": 4,
     *                              "valves": "8",
     *                              "engine_type": "V2",
     *                              "engine_codes": "KWA456",
     *                              "kba": "45689722"
     *                     }
     *    )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Lista znalezionych samochodów pasujących do danego kryterium",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                              "manufacturer": "Opel",
     *                              "model": "Vectra",
     *                              "type": "C",
     *                              "model_from": "2002-09",
     *                              "model_to": "2004-5",
     *                              "body_type": "Sedan",
     *                              "drive_type": "FWD",
     *                              "displacement_liters": "1655",
     *                              "displacement_cmm": "1655",
     *                              "fuel_type": "Gas",
     *                              "kw": "90",
     *                              "hp": "120",
     *                              "cylinders": 4,
     *                              "valves": "8",
     *                              "engine_type": "V2",
     *                              "engine_codes": "KWA456",
     *                              "kba": "45689722"
     *                     }
     *             )
     *         })
     * )
     **/
    #[Route('/car', name: 'app_car_get', methods: ["GET"])]
    public function index(CarRepository $carRepository, Request $request = null)
    {
        /* Najprostsza metoda do pobrania samochodów przyjmuje obiekt i wyszukuje po jego polach w bazie danych */
        /* Jeżeli nie przekazano nic w body request to zwracanie wszystkich samochodów */
        try {
            $requestArray = $request->toArray();
            $cars = $carRepository->findBy($requestArray);
        } catch (\Exception $exception) {
            if($exception->getMessage() == "Request body is empty.") {
                $cars = $carRepository->findAll();
            }else{
                throw $exception;
            }
        }
        return $this->json($cars);
    }

    /**
     * Tworzy samochód
     *
     *
     * @OA\Tag(name="Car")
     * @OA\RequestBody(
     *     request="CarPostRequestBody",
     *     description="Właściwości samochodu",
     *     required=true,
     *     @OA\JsonContent(
     *                     example={
     *                              "manufacturer": "Opel",
     *                              "model": "Vectra",
     *                              "type": "C",
     *                              "model_from": "2002-09",
     *                              "model_to": "2004-5",
     *                              "body_type": "Sedan",
     *                              "drive_type": "FWD",
     *                              "displacement_liters": "1655",
     *                              "displacement_cmm": "1655",
     *                              "fuel_type": "Gas",
     *                              "kw": "90",
     *                              "hp": "120",
     *                              "cylinders": 4,
     *                              "valves": "8",
     *                              "engine_type": "V2",
     *                              "engine_codes": "KWA456",
     *                              "kba": "45689722"
     *                     }
     *    )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Stworzony samochód",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                              "id": 1,
     *                              "manufacturer": "Opel",
     *                              "model": "Vectra",
     *                              "type": "C",
     *                              "model_from": "2002-09",
     *                              "model_to": "2004-5",
     *                              "body_type": "Sedan",
     *                              "drive_type": "FWD",
     *                              "displacement_liters": "1655",
     *                              "displacement_cmm": "1655",
     *                              "fuel_type": "Gas",
     *                              "kw": "90",
     *                              "hp": "120",
     *                              "cylinders": 4,
     *                              "valves": "8",
     *                              "engine_type": "V2",
     *                              "engine_codes": "KWA456",
     *                              "kba": "45689722"
     *                     }
     *             )
     *         })
     * )
     **/
    #[Route('/car', name: 'app_car_post', methods: ["POST"])]
    public function post(CarRepository $carRepository, Request $request)
    {
        /* Dodawanie nowego samochodu do bazy danych */
        $requestArray = $request->toArray();
        $car = new Car();
        $car->setManufacturer($requestArray['manufacturer']);
        $car->setModel($requestArray['model']);
        $car->setType($requestArray['type']);
        $car->setModelFrom($requestArray['model_from']);
        $car->setModelTo($requestArray['model_to']);
        $car->setBodyType($requestArray['body_type']);
        $car->setDriveType($requestArray['drive_type']);
        $car->setDisplacementLiters($requestArray['displacement_liters']);
        $car->setDisplacementCmm($requestArray['displacement_cmm']);
        $car->setFuelType($requestArray['fuel_type']);
        $car->setKw($requestArray['kw']);
        $car->setHp($requestArray['hp']);
        $car->setCylinders($requestArray['cylinders']);
        $car->setValves($requestArray['valves']);
        $car->setEngineType($requestArray['engine_type']);
        $car->setEngineCodes($requestArray['engine_codes']);
        $car->setKba($requestArray['kba']);
        $carRepository->save($car, true);
        return $this->json($car);
    }

    /**
     * Aktualizuje samochód
     *
     *
     * @OA\Tag(name="Car")
     * @OA\RequestBody(
     *     request="CarPutRequestBody",
     *     description="Właściwości samochodu",
     *     required=true,
     *     @OA\JsonContent(
     *                     example={
     *                              "id": 1,
     *                              "manufacturer": "Opel",
     *                              "model": "Vectra",
     *                              "type": "C",
     *                              "model_from": "2002-09",
     *                              "model_to": "2004-5",
     *                              "body_type": "Sedan",
     *                              "drive_type": "FWD",
     *                              "displacement_liters": "1655",
     *                              "displacement_cmm": "1655",
     *                              "fuel_type": "Gas",
     *                              "kw": "90",
     *                              "hp": "120",
     *                              "cylinders": 4,
     *                              "valves": "8",
     *                              "engine_type": "V2",
     *                              "engine_codes": "KWA456",
     *                              "kba": "45689722"
     *                     }
     *    )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Stworzony samochód",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                              "id": 1,
     *                              "manufacturer": "Opel",
     *                              "model": "Vectra",
     *                              "type": "C",
     *                              "model_from": "2002-09",
     *                              "model_to": "2004-5",
     *                              "body_type": "Sedan",
     *                              "drive_type": "FWD",
     *                              "displacement_liters": "1655",
     *                              "displacement_cmm": "1655",
     *                              "fuel_type": "Gas",
     *                              "kw": "90",
     *                              "hp": "120",
     *                              "cylinders": 4,
     *                              "valves": "8",
     *                              "engine_type": "V2",
     *                              "engine_codes": "KWA456",
     *                              "kba": "45689722"
     *                     }
     *             )
     *         })
     * )
     **/
    #[Route('/car', name: 'app_car_put', methods: ["PUT"])]
    public function put(CarRepository $carRepository, Request $request)
    {
        /* Aktualizowanie samochodu */
        $requestArray = $request->toArray();
        $car = $carRepository->findOneBy(['id' => $requestArray['id']]);
        $car->setManufacturer($requestArray['manufacturer']);
        $car->setModel($requestArray['model']);
        $car->setType($requestArray['type']);
        $car->setModelFrom($requestArray['model_from']);
        $car->setModelTo($requestArray['model_to']);
        $car->setBodyType($requestArray['body_type']);
        $car->setDriveType($requestArray['drive_type']);
        $car->setDisplacementLiters($requestArray['displacement_liters']);
        $car->setDisplacementCmm($requestArray['displacement_cmm']);
        $car->setFuelType($requestArray['fuel_type']);
        $car->setKw($requestArray['kw']);
        $car->setHp($requestArray['hp']);
        $car->setCylinders($requestArray['cylinders']);
        $car->setValves($requestArray['valves']);
        $car->setEngineType($requestArray['engine_type']);
        $car->setEngineCodes($requestArray['engine_codes']);
        $car->setKba($requestArray['kba']);
        $carRepository->save($car, true);
        return $this->json($car);
    }

    /**
     * Usuwa samochód z bazy daych i odpina go od części do których był podłączony
     *
     *
     * @OA\Tag(name="Car")
     * @OA\RequestBody(
     *     request="CarDeleteRequestBody",
     *     description="Samochód do usunięcia",
     *     required=true,
     *     @OA\JsonContent(
     *                     example={
     *                              "id": 1,
     *                              "manufacturer": "Opel",
     *                              "model": "Vectra",
     *                              "type": "C",
     *                              "model_from": "2002-09",
     *                              "model_to": "2004-5",
     *                              "body_type": "Sedan",
     *                              "drive_type": "FWD",
     *                              "displacement_liters": "1655",
     *                              "displacement_cmm": "1655",
     *                              "fuel_type": "Gas",
     *                              "kw": "90",
     *                              "hp": "120",
     *                              "cylinders": 4,
     *                              "valves": "8",
     *                              "engine_type": "V2",
     *                              "engine_codes": "KWA456",
     *                              "kba": "45689722"
     *                     }
     *    )
     * )
     * @OA\Response(
     *     response=200,
     *     description="Usunięty samochód",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                              "id": 1,
     *                              "manufacturer": "Opel",
     *                              "model": "Vectra",
     *                              "type": "C",
     *                              "model_from": "2002-09",
     *                              "model_to": "2004-5",
     *                              "body_type": "Sedan",
     *                              "drive_type": "FWD",
     *                              "displacement_liters": "1655",
     *                              "displacement_cmm": "1655",
     *                              "fuel_type": "Gas",
     *                              "kw": "90",
     *                              "hp": "120",
     *                              "cylinders": 4,
     *                              "valves": "8",
     *                              "engine_type": "V2",
     *                              "engine_codes": "KWA456",
     *                              "kba": "45689722"
     *                     }
     *             )
     *         })
     * )
     **/
    #[Route('/car/{id_car}', name: 'app_car_delete', methods: ["DELETE"])]
    public function delete(CarRepository $carRepository, int $id_car)
    {
        /* Aktualizowanie samochodu */
        $car = $carRepository->findOneBy(['id' => $id_car]);
        $carRepository->remove($car, true);
        return $this->json($car);
    }

    /**
     * Zwraca samochód pasujący do wyszukanej frazy
     *
     *
     * @OA\Tag(name="Car")
     * @OA\RequestBody(
     *     request="CarGetRequestBody",
     *     description="Parametry samochodu do wyszukania",
     *     required=false
     * )
     * @OA\Response(
     *     response=200,
     *     description="Lista znalezionych samochodów pasujących do danego ciągu txt",
     *     content={
     *             @OA\MediaType(
     *                 mediaType="application/json",
     *                     example={
     *                              "manufacturer": "Opel",
     *                              "model": "Vectra",
     *                              "type": "C",
     *                              "model_from": "2002-09",
     *                              "model_to": "2004-5",
     *                              "body_type": "Sedan",
     *                              "drive_type": "FWD",
     *                              "displacement_liters": "1655",
     *                              "displacement_cmm": "1655",
     *                              "fuel_type": "Gas",
     *                              "kw": "90",
     *                              "hp": "120",
     *                              "cylinders": 4,
     *                              "valves": "8",
     *                              "engine_type": "V2",
     *                              "engine_codes": "KWA456",
     *                              "kba": "45689722"
     *                     }
     *             )
     *         })
     * )
     **/
    #[Route('/car/search/{text_value}', name: 'app_car_search', methods: ["GET"])]
    public function search(CarRepository $carRepository, string $text_value)
    {
        /* Metoda wyszukuje samochód na podstawie wprowadzonego tekstu */
        $result = $carRepository->search($text_value);
        return $this->json($result);
    }
}


