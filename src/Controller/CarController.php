<?php

namespace App\Controller;

use App\Entity\Car;
use App\Repository\ArticleCarRepository;
use App\Repository\CarRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Attribute\Model;
use App\HttpRequestModel\CarSearchRequest;
use App\HttpRequestModel\CarCreateRequest;
use App\HttpRequestModel\CarUpdateRequest;
use App\HttpRequestModel\CarSearchExtendedRequest;
use App\HttpResponseModel\CarListResponse;
use App\HttpResponseModel\CarDeleteResponse;

class CarController extends AbstractController
{

    /**
     * Zwraca samochód pasujący do kryteriów RequestBody
     */
    #[OA\Tag(name: "Car")]
    #[OA\RequestBody(
        description: "Parametry samochodu do wyszukania",
        required: false,
        content: new Model(type: CarSearchRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Lista znalezionych samochodów pasujących do danego kryterium",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Car::class))
        )
    )]
    #[Route('/car/get', name: 'app_car_get', methods: ["POST"])]
    public function index(CarRepository $carRepository, Request $request = null)
    {
        /* Najprostsza metoda do pobrania samochodów przyjmuje obiekt i wyszukuje po jego polach w bazie danych */
        /* Jeżeli nie przekazano nic w body request to zwracanie wszystkich samochodów */
        /* Jezeli żaden samochód nie pasuje do parametrów przekazanych w RequestBody lub nie ma nic w bazie to 404 */
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
        if(!$cars) return new JsonResponse(['message' => 'Nie znaleziono'], 404);
        return new JsonResponse($cars);
    }

    /**
     * Tworzy samochód
     */
    #[OA\Tag(name: "Car")]
    #[OA\RequestBody(
        description: "Właściwości samochodu",
        required: true,
        content: new Model(type: CarCreateRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Stworzony samochód",
        content: new Model(type: Car::class)
    )]
    #[OA\Response(
        response: 400,
        description: "W bazie już istnieje samochód o dokładnie takich samych paraetrach"
    )]
    #[Route('/car', name: 'app_car_post', methods: ["POST"])]
    public function post(CarRepository $carRepository, Request $request)
    {
        $requestArray = $request->toArray();
        /* Sprawdzanie czy w bazie nie istnieje już samochód o identycznych parametrach */
        $car = $carRepository->findOneBy($requestArray);
        if($car !== null) return new JsonResponse(['message' => 'Samochód o identycznych parametrach już istnieje w bazie danych'], 400);
        /* Dodawanie nowego samochodu do bazy danych */
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
        $car->setTextValue(implode(" ", (array)$car));
        $carRepository->save($car, true);
        return new JsonResponse($car);
    }

    /**
     * Aktualizuje samochód
     */
    #[OA\Tag(name: "Car")]
    #[OA\RequestBody(
        description: "Właściwości samochodu",
        required: true,
        content: new Model(type: CarUpdateRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Zaktualizowany samochód",
        content: new Model(type: Car::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono samochodu o podanym id"
    )]
    #[Route('/car', name: 'app_car_put', methods: ["PUT"])]
    public function put(CarRepository $carRepository, Request $request)
    {
        /* Aktualizowanie samochodu */
        $requestArray = $request->toArray();
        $car = $carRepository->findOneBy(['id' => $requestArray['id']]);
        if(!$car) return new JsonResponse(['message' => 'Nie znaleziono samochodu o podanym id'], 404);
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
        //$car->setTextValue(implode(" ", (array)$car));
        
        $carRepository->save($car, true);
        return new JsonResponse($car);
    }

    /**
     * Usuwa samochód z bazy daych i odpina go od części do których był podłączony
     */
    #[OA\Tag(name: "Car")]
    #[OA\Response(
        response: 200,
        description: "Usunięty samochód",
        content: new Model(type: CarDeleteResponse::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono samochodu o podanym id"
    )]
    #[Route('/car/{id_car}', name: 'app_car_delete', methods: ["DELETE"])]
    public function delete(CarRepository $carRepository, ArticleCarRepository $articleCarRepository, int $id_car)
    {
        $car = $carRepository->findOneBy(['id' => $id_car]);
        if(!$car) return new JsonResponse(['message' => 'Nie znaleziono samochodu o podanym id']);
        /* Najpierw odpinanie samochodu od wszystkich produktów do których został podpięty */
        $articleCars= $articleCarRepository->findBy(['id_car' => $id_car]);
        $deletedArticleCars = 0;
        foreach ($articleCars as $articleCar) {
            $articleCarRepository->remove($articleCar, true);
            $deletedArticleCars++;
        }
        $carRepository->remove($car, true);
        return new JsonResponse(['message' => 'Usunięto samochód oraz odpięto od '.$deletedArticleCars.' produktów.', 'deletedItem' => $car]);
    }

    /**
     * Zwraca samochód pasujący do wyszukanej frazy
     */
    #[OA\Tag(name: "Car")]
    #[OA\Response(
        response: 200,
        description: "Lista znalezionych samochodów pasujących do danego ciągu txt",
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Car::class))
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono samochodu na podstawie wpisanej frazy"
    )]
    #[Route('/car/search/{text_value}', name: 'app_car_search', methods: ["GET"])]
    public function search(CarRepository $carRepository, string $text_value)
    {
        /* Metoda wyszukuje samochód na podstawie wprowadzonego tekstu */
        $result = $carRepository->search($text_value);
        if(!$result) return new JsonResponse(['message' => 'Nie znaleziono samochodu na podstawie wpisanej frazy'], 404);
        return new JsonResponse($result);
    }

    /**
     * Zwraca listę samochodów pasujących do filtrów w RequestBody
     */
    #[OA\Tag(name: "Car")]
    #[OA\RequestBody(
        description: "Filtry do wyszukiwania samochodów",
        required: false,
        content: new Model(type: CarSearchExtendedRequest::class)
    )]
    #[OA\Response(
        response: 200,
        description: "Lista znalezionych samochodów pasujących do filtrów",
        content: new Model(type: CarListResponse::class)
    )]
    #[OA\Response(
        response: 404,
        description: "Nie znaleziono samochodów pasujących do filtrów"
    )]
    #[Route('/car/search', name: 'app_car_search_post', methods: ["POST"])]
    public function searchPost(CarRepository $carRepository, Request $request = null)
    {
        /* Metoda wyszukuje samochody na podstawie filtrów przekazanych w RequestBody */
        try {
            $requestArray = $request->toArray();
            $criteria = $requestArray['criteria'] ?? [];
            $orderBy = $requestArray['orderBy'] ?? [];
            $limit = $requestArray['limit'] ?? 50;
            $offset = $requestArray['offset'] ?? 0;
            
            $cars = $carRepository->findByExtended($criteria, $orderBy, $limit, $offset);
            $total = $carRepository->countByExtended($criteria);

        } catch (\Exception $exception) {
            if($exception->getMessage() == "Request body is empty.") {
                $cars = $carRepository->findAll();
                $total = count($cars);
            } else {
                throw $exception;
            }
        }
        
        if(!$cars) return new JsonResponse(['message' => 'Nie znaleziono'], 404);
        
        return new JsonResponse(['data' => $cars, 'total' => $total]);
    }
}


