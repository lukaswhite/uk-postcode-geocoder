<?php

use Lukaswhite\UkPostcodeGeocoder\Service;

class ServiceTest extends \PHPUnit\Framework\TestCase
{
    public function testGet( )
    {
        $filename = sprintf( 'pc_%s.sqlite', uniqid( ) );
        $filepath = sprintf( '%s/%s', sys_get_temp_dir(), $filename );

        $provisioner = new \Lukaswhite\UkPostcodeGeocoder\Provisioner(
            sys_get_temp_dir( ),
            __DIR__ . '/fixtures/ONSPD_AUG_2018_UK.csv',
            $filename,
            true
        );
        $provisioner->run( );
        $this->assertFileExists( $filepath );

        $postcodes = new Service( sys_get_temp_dir( ), $filename );

        $coordinates = $postcodes->get( 'AB1 0AG' );
        $this->assertInstanceOf( \Lukaswhite\UkPostcodeGeocoder\Coordinate::class, $coordinates );
        $this->assertEquals( 57.097085, $coordinates->getLatitude( ) );
        $this->assertEquals( -2.267513, $coordinates->getLongitude( ) );

        $this->assertEquals(
            [
                'latitude'  =>  57.097085,
                'longitude' =>  -2.267513,
            ],
            $coordinates->toArray( )
        );

        $this->assertEquals(
            [
                'latitude'  =>  57.097085,
                'longitude' =>  -2.267513,
            ],
            json_decode( json_encode( $coordinates ), true )
        );

        $this->assertNull( $postcodes->get( 'WN3 4AQ' ) );

        unlink( $filepath );
    }

    public function testGetMultiple( )
    {
        $filename = sprintf( 'pc_%s.sqlite', uniqid( ) );
        $filepath = sprintf( '%s/%s', sys_get_temp_dir(), $filename );

        $provisioner = new \Lukaswhite\UkPostcodeGeocoder\Provisioner(
            sys_get_temp_dir( ),
            __DIR__ . '/fixtures/ONSPD_AUG_2018_UK.csv',
            $filename,
            true
        );
        $provisioner->run( );
        $this->assertFileExists( $filepath );

        $postcodes = new Service( sys_get_temp_dir( ), $filename );

        $results = $postcodes->getMultiple( 'AB1 0AG', 'AB1 0AL', 'AB1 0AD' );

        $this->assertTrue( is_array( $results ) );
        $this->assertEquals( 3, count( $results ) );
        $this->assertArrayHasKey( 'AB1 0AG', $results );

        $coordinates = $results[ 'AB1 0AG' ];
        $this->assertInstanceOf( \Lukaswhite\UkPostcodeGeocoder\Coordinate::class, $coordinates );
        $this->assertEquals( 57.097085, $coordinates->getLatitude( ) );
        $this->assertEquals( -2.267513, $coordinates->getLongitude( ) );


        $noResults = $postcodes->getMultiple( 'WN3 4AQ', 'BG3 5TF' );

        $this->assertTrue( is_array( $noResults ) );
        $this->assertEquals( 0, count( $noResults ) );

        unlink( $filepath );
    }

    public function testAdd( )
    {
        $filename = sprintf( 'pc_%s.sqlite', uniqid( ) );
        $filepath = sprintf( '%s/%s', sys_get_temp_dir(), $filename );

        $provisioner = new \Lukaswhite\UkPostcodeGeocoder\Provisioner(
            sys_get_temp_dir( ),
            __DIR__ . '/fixtures/ONSPD_AUG_2018_UK.csv',
            $filename,
            true
        );
        $provisioner->run( );
        $this->assertFileExists( $filepath );

        $postcodes = new Service( sys_get_temp_dir( ), $filename );

        $postcodes->add(
            'sw1A2aa',
            new \Lukaswhite\UkPostcodeGeocoder\Coordinate( 51.50354, -0.127695 )
        );

        $coordinates = $postcodes->get( 'SW1A 2AA' );

        $this->assertEquals( 51.50354, $coordinates->getLatitude( ) );
        $this->assertEquals( -0.127695, $coordinates->getLongitude( ) );
    }

    public function testAddWithInvalidPostcodeThrowsException( )
    {
        $this->expectException(\Lukaswhite\UkPostcodeGeocoder\Exceptions\InvalidPostcodeException::class);
        $filename = sprintf( 'pc_%s.sqlite', uniqid( ) );
        $filepath = sprintf( '%s/%s', sys_get_temp_dir(), $filename );

        $provisioner = new \Lukaswhite\UkPostcodeGeocoder\Provisioner(
            sys_get_temp_dir( ),
            __DIR__ . '/fixtures/ONSPD_AUG_2018_UK.csv',
            $filename,
            true
        );
        $provisioner->run( );
        $this->assertFileExists( $filepath );

        $postcodes = new Service( sys_get_temp_dir( ), $filename );

        $postcodes->add(
            'not a postcode',
            new \Lukaswhite\UkPostcodeGeocoder\Coordinate( 51.50354, -0.127695 )
        );
    }

    public function testAddExistingThrowsException( )
    {
        $this->expectException(\Lukaswhite\UkPostcodeGeocoder\Exceptions\DuplicatePostcodeException::class);
        $filename = sprintf( 'pc_%s.sqlite', uniqid( ) );
        $filepath = sprintf( '%s/%s', sys_get_temp_dir(), $filename );

        $provisioner = new \Lukaswhite\UkPostcodeGeocoder\Provisioner(
            sys_get_temp_dir( ),
            __DIR__ . '/fixtures/ONSPD_AUG_2018_UK.csv',
            $filename,
            true
        );
        $provisioner->run( );
        $this->assertFileExists( $filepath );

        $postcodes = new Service( sys_get_temp_dir( ), $filename );

        $postcodes->add(
            'AB1 0AG',
            new \Lukaswhite\UkPostcodeGeocoder\Coordinate( 57.097085, -2.267513 )
        );
    }

    public function testThrowsExceptionIfDatabaseDoesNotExist( )
    {
        $this->expectException(\Lukaswhite\UkPostcodeGeocoder\Exceptions\DatabaseDoesNotExistException::class);
        $postcodes = new Service( '/this/directory/does/not/exist' );
    }

}