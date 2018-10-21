<?php

namespace Lukaswhite\Postcodes;

use League\Csv\Reader;
use League\Csv\Statement;
use Lukaswhite\UkPostcodeGeocoder\Exceptions\DirectoryException;
use Medoo\Medoo;
use SQLite3;
use League\CLImate\CLImate;

/**
 * Class Provisioner
 *
 * @package Lukaswhite\Postcodes
 */
class Provisioner
{
    /**
     * The path to the database
     *
     * @var string
     */
    protected $databasePath;

    /**
     * The directory that should house the database
     *
     * @var string
     */
    protected $databaseDirectory;

    /**
     * The filename of the database
     *
     * @var string
     */
    protected $databaseFilename;

    /**
     * The path to the CSV file that contains the ONS data
     *
     * @var string
     */
    protected $csvFilepath;

    /**
     * The number of records to insert at a time
     *
     * @var int
     */
    protected $perPage = 2500;

    /**
     * The database connection
     *
     * @var Medoo
     */
    protected $database;

    /**
     * The CLI library for showing messages and reporting progress
     *
     * @var CLImate
     */
    protected $output;

    /**
     * Provisioner constructor.
     *
     * @param string $databaseDirectory
     * @param string $csvFilepath
     * @param string $filename
     *
     * @throws DirectoryException
     */
    public function __construct(
        string $databaseDirectory,
        string $csvFilepath,
        string $filename = 'postcodes.sqlite' )
    {
        $this->databaseDirectory    =   $databaseDirectory;

        if ( file_exists( $this->databaseDirectory ) && ! is_dir( $this->databaseDirectory ) ) {
            throw new DirectoryException( 'The path provided is not a directory' );
        }

        // @codeCoverageIgnoreStart
        if ( file_exists( $this->databaseDirectory ) && ! is_writable( $this->databaseDirectory ) ) {
            throw new DirectoryException( 'The path provided is not a writeable' );
        }

        if ( ! file_exists( $this->databaseDirectory ) ) {
            if ( ! $this->createDirectory( ) ) {
                throw new DirectoryException( 'The database directory could not be created' );
            }
        }
        // @codeCoverageIgnoreEnd

        // Set the paths
        $this->csvFilepath          =   $csvFilepath;
        $this->databaseFilename     =   $filename;

        // We'll log to the console
        $this->output = new CLImate( );
    }

    /**
     * Run the provisioner
     */
    public function run( )
    {
        $this->databasePath = $this->databaseDirectory . DIRECTORY_SEPARATOR . $this->databaseFilename;

        if ( ! file_exists( $this->databaseFilename ) ) {
            $this->createDatabase( );
        }

        $db = new SQLite3( $this->databasePath );

        $this->populate( );

        $this->compactDatabase( );
    }

    /**
     * Get the number of rows in the CSV file
     *
     * @return int
     */
    public function getNumberOfRows( ): int
    {
        $linecount = 0;
        $handle = fopen( $this->csvFilepath, "r" );
        while(!feof($handle)){
            $line = fgets($handle);
            $linecount++;
        }

        fclose($handle);

        return ( $linecount - 1 ); // ignore the header row
    }

    /**
     * Set the number of records to insert at a time
     *
     * @param int $perPage
     */
    public function setPerPage( int $perPage )
    {
        $this->perPage = $perPage;
    }

    /**
     * Create the directory to house the database
     *
     * @return bool
     */
    protected function createDirectory( )
    {
        return mkdir( $this->databaseDirectory, 0775, true );
    }

    /**
     * Create the database
     *
     * @return bool
     */
    protected function createDatabase( )
    {
        $db = new SQLite3( $this->databasePath );

        // Create the postcodes table
        $db->exec(
            'CREATE TABLE IF NOT EXISTS postcodes (
            postcode VARCHAR(12) PRIMARY KEY,
            latitude FLOAT,
            longitude FLOAT
        )');

        // Create a table that will keep track of the imported files
        $db->exec(
            'CREATE TABLE IF NOT EXISTS imported_files (
            filename VARCHAR(64),
            imported_at TEXT
        )');

        $this->database = new Medoo( [
            'database_type' => 'sqlite',
            'database_file' => $this->databasePath,
        ] );

    }

    /**
     * Show a message to the console.
     *
     * Note that the message is deliberately suppressed if this class is not
     * being run from the command-line, or it's being run in tests.
     *
     * @param $message
     */
    protected function displayMessage( $message )
    {
        if ( ( php_sapi_name( ) === 'cli' ) && ! defined( 'PHPUNIT_POSTCODES_TESTSUITE' ) ) {
            // @codeCoverageIgnoreStart
            $this->output->output( $message );
            // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Populate the postcodes database
     *
     * @throws \League\Csv\Exception
     */
    protected function populate( )
    {
        $this->displayMessage( 'Please wait, while the postcodes are counted' );

        // This is used for "pagination"
        $total = $this->getNumberOfRows( );
        $remaining = $total;
        $offset = 0;

        $progress = $this->output->progress( )->total( $total );
        $this->displayMessage( sprintf( 'Importing %d postcodes', $total ) );

        // Populate the postcodes table
        $reader = Reader::createFromPath( $this->csvFilepath )->setHeaderOffset( 0 );

        do {

            $limit = ( $remaining <= $this->perPage ) ? $remaining : $this->perPage;

            $rows =  [ ];

            $stmt = ( new Statement( ) )
                ->offset( $offset )
                ->limit( $limit );

            $records = $stmt->process( $reader );

            foreach ( $records as $record ) {

                $rows[ ] = [
                    'postcode'      =>  $record['pcds'],
                    'latitude'      =>  $record['lat'],
                    'longitude'     =>  $record['long'],
                ];
            }

            $this->database->insert('postcodes', $rows );

            $remaining -= $limit;
            $offset += $limit;

            if ( ( php_sapi_name( ) === 'cli' ) && ! defined( 'PHPUNIT_POSTCODES_TESTSUITE' ) ) {
                // @codeCoverageIgnoreStart
                $progress->current($total - $remaining);
                // @codeCoverageIgnoreEnd
            }


        } while ( $remaining > 0 );

        $this->displayMessage( sprintf( 'Finished inserting %d postcodes.', $total ) );

        // Record this import
        $this->database->insert(
            'imported_files',
            [
                'filename'      =>  basename( $this->csvFilepath ),
                'imported_at'   =>  Medoo::raw( 'DATETIME("now")' ),
            ]
        );
    }

    /**
     * Compacts the database
     *
     * @return void
     */
    protected function compactDatabase( )
    {
        $this->displayMessage( 'Compacting database...' );
        $this->database->query( 'VACUUM;' );
        $this->displayMessage( '...done' );
    }

}