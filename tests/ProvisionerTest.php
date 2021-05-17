<?php

use Lukaswhite\UkPostcodeGeocoder\Service;

class ProvisionerTest extends \PHPUnit\Framework\TestCase
{
    public function testProvisioning( )
    {
        $filename = sprintf( 'pc_%s.sqlite', uniqid( ) );

        $filepath = sprintf( '%s/%s', sys_get_temp_dir(), $filename );

        $provisioner = new \Lukaswhite\UkPostcodeGeocoder\Provisioner(
            sys_get_temp_dir( ),
            __DIR__ . '/fixtures/ONSPD_AUG_2018_UK.csv',
            $filename
        );

        $provisioner->setPerPage( 10 );

        $this->assertEquals( 108, $provisioner->getNumberOfRows( ) );

        $provisioner->run( );
        $this->assertFileExists( $filepath );

        $postcodes = new Service( sys_get_temp_dir( ), $filename );

        $this->assertEquals( 108, $postcodes->totalRecords( ) );

        unlink( $filepath );
    }

    public function testCreateDirectory( )
    {
        do {
            $directory = sprintf( '%s/%s', sys_get_temp_dir( ), uniqid( ) );
        } while ( file_exists( $directory ) );

        $provisioner = new \Lukaswhite\UkPostcodeGeocoder\Provisioner(
            $directory,
            __DIR__ . '/fixtures/ONSPD_AUG_2018_UK.csv'
        );

        $this->assertTrue( file_exists( $directory ) );
        $this->assertTrue( is_dir( $directory ) );

        rmdir( $directory );

    }

    public function testExceptionThrownIfDirectoryIsNotDirectory( )
    {
        $this->expectException(\Lukaswhite\UkPostcodeGeocoder\Exceptions\DirectoryException::class);
        $filename = tempnam( sys_get_temp_dir( ), 'pc_' );

        $provisioner = new \Lukaswhite\UkPostcodeGeocoder\Provisioner(
            $filename,
            __DIR__ . '/fixtures/ONSPD_AUG_2018_UK.csv'
        );
    }
}