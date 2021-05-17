<?php

namespace Lukaswhite\UkPostcodeGeocoder;

use Lukaswhite\UkPostcodeGeocoder\Coordinate;
use Lukaswhite\UkPostcodeGeocoder\Exceptions\DatabaseDoesNotExistException;
use Lukaswhite\UkPostcodeGeocoder\Exceptions\DuplicatePostcodeException;
use Lukaswhite\UkPostcodeGeocoder\Exceptions\InvalidPostcodeException;
use Lukaswhite\UkPostcode\UkPostcode;
use Medoo\Medoo;
use phpDocumentor\Reflection\Types\String_;

/**
 * Class Service
 *
 * @package Lukaswhite\UkPostcodeGeocoder
 */
class Service
{
    /**
     * The database
     *
     * @var Medoo
     */
    protected $database;

    /**
     * Postcodes constructor.
     *
     * @param string $databasePath
     * @throws DatabaseDoesNotExistException
     */
    public function __construct( string $databaseDirectory, string $databaseFilename = 'postcodes.sqlite' )
    {
        $databasePath = $databaseDirectory . DIRECTORY_SEPARATOR . $databaseFilename;

        if ( ! file_exists( $databasePath ) ) {
            throw new DatabaseDoesNotExistException( 'The database does not exist. Have you provisioned it?' );
        }

        $this->database = new Medoo( [
            'database_type'     =>  'sqlite',
            'database_file'     =>  $databasePath,
        ] );
    }

    /**
     * Get the latitude and longitude of a postcode
     *
     * @param string $postcode
     * @return Coordinate
     */
    public function get( string $postcode ) : ?Coordinate
    {
        $result = $this->database->get('postcodes', [
            'latitude',
            'longitude',
        ], [
            'postcode' => $postcode,
        ]);

        if ( $result ) {
            return new Coordinate( floatval($result['latitude']), floatval($result['longitude']) );
        }

        return null;
    }

    /**
     * Get multiple postcodes
     *
     * @param string ...$postcodes
     * @return array
     */
    public function getMultiple( ...$postcodes ) : array
    {
        $results = $this->database->select('postcodes', [
            'postcode',
            'latitude',
            'longitude',
        ], [
            'postcode' => $postcodes,
        ] );

        if ( ! count( $results ) ) {
            return [ ];
        }

        $postcodes = [ ];

        foreach( $results as $result ) {


            $postcodes[ $result[ 'postcode' ] ] = new Coordinate( $result[ 'latitude' ], $result[ 'longitude' ] );
        }

        return $postcodes;

    }

    /**
     * Add a postcode to the database
     *
     * @param string $postcode
     * @param Coordinate $coordinate
     * @return self
     * @throws InvalidPostcodeException
     * @throws DuplicatePostcodeException
     */
    public function add( string $postcode, Coordinate $coordinate ) : self
    {
        $postcode = new UkPostcode( $postcode );

        if ( ! $postcode->isValid( ) ) {
            throw new InvalidPostcodeException( 'Invalid postcode' );
        }

        if ( $this->get( $postcode->formatted( ) ) ) {
            throw new DuplicatePostcodeException( 'That postcode is already in the database' );
        }

        $this->database->insert(
            'postcodes',
            [
                'postcode'      =>  $postcode->formatted( ),
                'latitude'      =>  $coordinate->getLatitude( ),
                'longitude'     =>  $coordinate->getLongitude( ),
            ]
        );

        return $this;
    }

    /**
     * Get a random postcode
     *
     * @return string
     */
    public function random(): string
    {
        $result = $this->database->rand(
            'postcodes',
            [
                'postcode'
            ],
            [
                'LIMIT' => 1
            ]
        );
        return $result[0]['postcode'];
    }

    /**
     * Returns the total number of postcodes in the database.
     *
     * @return int
     */
    public function totalRecords( )
    {
        return $this->database->count( 'postcodes' );
    }
}