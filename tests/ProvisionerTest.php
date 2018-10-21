<?php

use Lukaswhite\UkPostcodeGeocoder\Service;

class ProvisionerTest extends \PHPUnit\Framework\TestCase
{
    public function testProvisioning( )
    {
        $filename = sprintf( 'pc_%s.sqlite', uniqid( ) );

        $filepath = sprintf( '%s/%s', sys_get_temp_dir(), $filename );

        $provisioner = new \Lukaswhite\Postcodes\Provisioner(
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

        $provisioner = new \Lukaswhite\Postcodes\Provisioner(
            $directory,
            __DIR__ . '/fixtures/ONSPD_AUG_2018_UK.csv'
        );

        $this->assertTrue( file_exists( $directory ) );
        $this->assertTrue( is_dir( $directory ) );

        rmdir( $directory );

    }

    /**
     * @expectedException \Lukaswhite\UkPostcodeGeocoder\Exceptions\DirectoryException
     * @expectedExceptionMessage The path provided is not a directory
     */
    public function testExceptionThrownIfDirectoryIsNotDirectory( )
    {
        $filename = tempnam( sys_get_temp_dir( ), 'pc_' );

        $provisioner = new \Lukaswhite\Postcodes\Provisioner(
            $filename,
            __DIR__ . '/fixtures/ONSPD_AUG_2018_UK.csv'
        );
    }
}