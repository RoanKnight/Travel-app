<?php

namespace App\Services;

use GuzzleHttp\Client;

class AmadeusService
{
  protected $client;

  public function __construct()
  {
    $this->client = new Client([
      'base_uri' => 'https://test.api.amadeus.com',
      'headers' => [
        'Accept' => 'application/json',
      ],
    ]);
  }

  public function getAccessToken()
  {
    $clientId = env('AMADEUS_CLIENT_ID');
    $clientSecret = env('AMADEUS_CLIENT_SECRET');

    $credentials = base64_encode($clientId . ':' . $clientSecret);

    try {
      $response = $this->client->request('POST', '/v1/security/oauth2/token', [
        'headers' => [
          'Authorization' => 'Basic ' . $credentials,
        ],
        'form_params' => [
          'grant_type' => 'client_credentials',
        ],
      ]);

      $data = json_decode($response->getBody(), true);

      if (!isset($data['access_token'])) {
        throw new \Exception('Access token not found in the response');
      }

      return $data['access_token'];
    } catch (\Exception $e) {
      error_log($e->getMessage());

      throw $e;
    }
  }

  public function getFlightOffers($origin, $destination, $departureDate, $returnDate, $adults)
  {
    $accessToken = $this->getAccessToken();

    $response = $this->client->request('GET', "/v2/shopping/flight-offers", [
      'query' => [
        'originLocationCode' => $origin,
        'destinationLocationCode' => $destination,
        'departureDate' => $departureDate,
        'returnDate' => $returnDate,
        'adults' => $adults,
      ],
      'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
      ],
    ]);

    return json_decode($response->getBody(), true);
  }

  public function searchAirportsAndCities($keyword)
  {
    $accessToken = $this->getAccessToken();

    $response = $this->client->request('GET', "/v1/reference-data/locations", [
      'query' => [
        'subType' => 'AIRPORT,CITY',
        'keyword' => $keyword,
      ],
      'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
      ],
    ]);

    return json_decode($response->getBody(), true);
  }

  public function getFlightStatus($airlineCode, $flightNumber, $departureDate)
  {
    $accessToken = $this->getAccessToken();

    $response = $this->client->request('GET', "/v2/schedule/flights", [
      'query' => [
        'carrierCode' => $airlineCode,
        'flightNumber' => $flightNumber,
        'scheduledDepartureDate' => $departureDate,
      ],
      'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
      ],
    ]);

    return json_decode($response->getBody(), true);
  }

  public function searchHotels($cityCode, $checkInDate, $checkOutDate, $adults)
  {
    $accessToken = $this->getAccessToken();

    $response = $this->client->request('GET', "/v2/shopping/hotel-offers", [
      'query' => [
        'cityCode' => $cityCode,
        'checkInDate' => $checkInDate,
        'checkOutDate' => $checkOutDate,
        'adults' => $adults,
      ],
      'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
      ],
    ]);

    return json_decode($response->getBody(), true);
  }

  public function getHotelRatings($hotelId)
  {
    $accessToken = $this->getAccessToken();

    $response = $this->client->request('GET', "/v2/e-reputation/hotel-sentiments", [
      'query' => [
        'hotelIds' => $hotelId,
      ],
      'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
      ],
    ]);

    return json_decode($response->getBody(), true);
  }

  public function bookHotel($hotelId, $guestDetails, $paymentDetails)
  {
    $accessToken = $this->getAccessToken();

    $response = $this->client->request('POST', "/v1/booking/hotel-bookings", [
      'json' => [
        'hotelId' => $hotelId,
        'guests' => $guestDetails,
        'payments' => $paymentDetails,
      ],
      'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
      ],
    ]);

    return json_decode($response->getBody(), true);
  }
}
