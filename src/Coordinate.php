<?php

namespace Lukaswhite\UkPostcodeGeocoder;

/**
 * Class Coordinate
 *
 * A very simple representation of a lat/lng co-ordinate
 *
 * @package Lukaswhite\UkPostcodeGeocoder
 */
class Coordinate implements \JsonSerializable
{
    /**
     * The latitude
     *
     * @var float
     */
    protected $latitude;

    /**
     * The longitude
     *
     * @var float
     */
    protected $longitude;

    /**
     * Coordinate constructor.
     *
     * @param float $latitude
     * @param float $longitude
     */
    public function __construct( float $latitude, float $longitude )
    {
        $this->latitude     =   $latitude;
        $this->longitude    =   $longitude;
    }

    /**
     * Get the latitude
     *
     * @return float
     */
    public function getLatitude( ) : float
    {
        return $this->latitude;
    }

    /**
     * Get the longitude
     *
     * @return float
     */
    public function getLongitude( ) : float
    {
        return $this->longitude;
    }

    /**
     * Create an array representation of this co-ordinate
     *
     * @return array
     */
    public function toArray( ) : array
    {
        return [
            'latitude'      =>  $this->latitude,
            'longitude'     =>  $this->longitude,
        ];
    }

    /**
     * Specify data which should be serialized to JSON
     *
     * @return array
     */
    public function jsonSerialize( ) : array
    {
        return $this->toArray( );
    }
}