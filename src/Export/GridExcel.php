<?php namespace KlbV2\Core\Export;


use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Exception;

/**
 * Class GridExcel
 *
 * @package App\KlbV2\Export
 */
class GridExcel
{
    /**
     * @var array
     *
     * [
     * 'c__order_no'              => [
     * 'title'  => 'Order No.',
     * 'format' => 'number',
     * ],
     * 'c__payment_method_code'   => [
     * 'title' => 'Payment Method',
     * ],
     * 'c__customer_paid_date'    => [
     * 'title'  => 'Customer Paid Date',
     * 'format' => 'date',
     * ],
     * 'c__bank_account_name'     => [
     * 'title' => 'Bank',
     * ],
     * 'c__paid_date'             => [
     * 'title'  => 'Transaction Date',
     * 'format' => 'date',
     * ],
     * 'c__subtotal_amount_fee'   => [
     * 'title'  => 'Nett MDR',
     * 'format' => 'currency',
     * ],
     * 'c__total_pay'             => [
     * 'title'  => 'Total Pay',
     * 'format' => 'currency',
     * ],
     * 'c__total_shipping_amount' => [
     * 'title'  => 'Total Shipping Amount',
     * 'format' => 'currency',
     * ],
     */
    protected $columns = [];
    /**
     * @var array
     */
    protected $items = [];
    /**
     * @var string example: 'download/sample_ar.xls'
     */
    protected $filename;
    /**
     * @var string
     */
    protected $file;
    /**
     * @var
     */
    protected $extension;
    /**
     * @var Spreadsheet
     */
    protected $spreadsheet;
    /**
     * @var string
     */
    protected $titleDocument;
    /**
     * @var array
     */
    protected $more = [];

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param array $columns
     *
     * @return GridExcel
     */
    public function setColumns( array $columns )
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @param array $items
     *
     * @return GridExcel
     */
    public function setItems( array $items )
    {
        $this->items = $items;

        return $this;
    }

    /**
     * @param       $name
     * @param array $more
     *
     * @return $this
     */
    public function addMore( $name, array $more )
    {
        $this->more[$name] = [
            'columns' => array_get( $more, 'columns', [] ),
            'items'   => array_get( $more, 'items', [] ),
            'title'   => array_get( $more, 'title', '' ),
        ];

        return $this;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @param string $filename
     *
     * @return GridExcel
     */
    public function setFilename( string $filename )
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @return string
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param null   $titleDocument
     * @param string $extension
     *
     * @return $this
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws Exception
     */
    public function generate( $titleDocument = null )
    {

        if ( null !== $titleDocument ) {
            $this->setTitleDocument( $titleDocument );
        }
        // Set document properties
        $this->getSpreadsheet()->getActiveSheet()
            ->getPageSetup()
            ->setOrientation( PageSetup::ORIENTATION_LANDSCAPE );
        $this->getSpreadsheet()->getActiveSheet()
            ->getPageSetup()
            ->setPaperSize( PageSetup::PAPERSIZE_A4 );
        $sheet = $this->getSpreadsheet()->setActiveSheetIndex( 0 );
        //Set style
        $styleColumn = [
            'font'    => [
                'bold'  => false,
                'size'  => 10,
                'color' => [
                    'argb' => Color::COLOR_WHITE,
                ],
            ],
            'borders' => [
                'top' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => [
                        'argb' => Color::COLOR_WHITE,
                    ],
                ],
            ],
            'fill'    => [
                'fillType'   => Fill::FILL_SOLID,
                'rotation'   => 90,
                'startColor' => [
                    'argb' => Color::COLOR_BLACK,
                ],
                'endColor'   => [
                    'argb' => Color::COLOR_BLACK,
                ],
            ],
        ];
        //set header

        $length = sizeof( $this->columns );
        $maxColumnName = Coordinate::stringFromColumnIndex( $length );
        $row = 1;
        $mergeCellName = 'A' . $row . ':' . $maxColumnName . $row;
        // Add some data
        $sheet->mergeCells( $mergeCellName );
        $sheet->getStyle( $mergeCellName )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );
        $sheet->getStyle( $mergeCellName )->getAlignment()->setVertical( Alignment::VERTICAL_CENTER );
        $richText = new RichText();
        $payable = $richText->createTextRun( $this->getTitleDocument() );
        $payable->getFont()->setSize( 12 );
        $payable->getFont()->setBold( true );
        $payable->getFont()->setColor( new Color( Color::COLOR_BLACK ) );
        $richText->createText( '.' );
        $sheet->getCell( 'A' . $row )->setValue( $richText );
        $sheet->getRowDimension( $row )->setRowHeight( 30 );

        $row++;
        $i = 1;
        $currencyStart = null;
        $columnSUM = [];
        foreach ( $this->columns as $column => $icolumn ) {
            $sheet->setCellValueByColumnAndRow( $i, $row, $icolumn['title'] );
            $this->columns[$column]['index'] = $i;
            $this->columns[$column]['name'] = Coordinate::stringFromColumnIndex( $i );
            $align = $sheet->getStyle( $this->columns[$column]['name'] )->getAlignment();
            $align->setWrapText( true );
            $align->setHorizontal( Alignment::HORIZONTAL_LEFT );
            $sheet->getColumnDimension( $this->columns[$column]['name'] )->setAutoSize( true );
            // style
            $styleRow = $sheet->getStyle( $this->columns[$column]['name'] . $row );
            $styleRow->applyFromArray( $styleColumn );
            $styleRow->getAlignment()->setHorizontal( Alignment::HORIZONTAL_LEFT );
            $styleRow->getAlignment()->setVertical( Alignment::VERTICAL_CENTER );
            /** Check for currency */
            if ( !empty( $icolumn['format'] ) ) {
                if ( $icolumn['format'] === 'currency' ) {
                    if ( null === $currencyStart ) {
                        $currencyStart = $this->columns[$column]['index'];
                    }
                    $columnSUM[] = $this->columns[$column]['name'];
                }
            }

            $i++;
        }
        $row++;
        $startItem = $row;
        if ( sizeof( $this->items ) > 0 ) {
            foreach ( $this->items as $item ) {
                foreach ( $item as $itemName => $itemValue ) {
                    if ( !isset( $this->columns[$itemName]['index'] ) ) {
                        continue;
                    }
                    $c = $this->columns[$itemName];

                    if ( !empty( $c['format'] ) ) {
                        excel_format( $sheet, $c['format'], $c['name'] . $row, $itemValue );
                    } else {
                        $sheet->getStyleByColumnAndRow( $this->columns[$itemName]['index'], $row )->getFont()->setSize( 9 );
                        $sheet->setCellValueByColumnAndRow( $this->columns[$itemName]['index'], $row, $itemValue );
                    }
                }
                $row++;
            }
        } else {
            $mergeCellName = 'A' . $row . ':' . $maxColumnName . $row;
            // Add some data
            $sheet->mergeCells( $mergeCellName );
            $sheet->getStyle( $mergeCellName )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );
            $sheet->getStyle( $mergeCellName )->getAlignment()->setVertical( Alignment::VERTICAL_CENTER );
            $sheet->getCell( 'A' . $row )->setValue( 'No Data' );
            $sheet->getStyle( 'A' . $row )->getFont()
                ->setSize( 9 )
                ->setItalic( true );
            $row++;
        }
        $endItem = $row;
        if ( count( $columnSUM ) > 0 ) {
//            $row++;
            $currencyStartName = Coordinate::stringFromColumnIndex( $currencyStart - 1 );
            $mergeCellName = 'A' . $row . ':' . $currencyStartName . $row;
            $sheet->mergeCells( $mergeCellName );
            $sheet->getStyle( $mergeCellName )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_RIGHT );
            $sheet->getStyle( 'A' . $row . ':' . $maxColumnName . $row )->applyFromArray( $styleColumn );
            $sheet->getCell( 'A' . $row )->setValue( 'Total' );
            foreach ( $columnSUM as $colSum ) {
                excel_format( $sheet, 'currency', $colSum . $row, "=SUM($colSum$startItem:$colSum$endItem)" );
            }
        }
        $row++;
        //Add more if set
        if ( sizeof( $this->more ) > 0 ) {
            foreach ( $this->more as $p ) {
                $length = sizeof( $p['columns'] );
                $maxColumnName = Coordinate::stringFromColumnIndex( $length );
                $row++;
                $mergeCellName = 'A' . $row . ':' . $maxColumnName . $row;
                // Add some data
                $sheet->mergeCells( $mergeCellName );
                $sheet->getStyle( $mergeCellName )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );
                $sheet->getStyle( $mergeCellName )->getAlignment()->setVertical( Alignment::VERTICAL_CENTER );
                $richText = new RichText();
                $payable = $richText->createTextRun( $p['title'] );
                $payable->getFont()->setSize( 12 );
                $payable->getFont()->setBold( true );
                $payable->getFont()->setColor( new Color( Color::COLOR_BLACK ) );
                $richText->createText( '.' );
                $sheet->getCell( 'A' . $row )->setValue( $richText );
                $sheet->getRowDimension( $row )->setRowHeight( 30 );

                $row++;
                $i = 1;
                $currencyStart = null;
                $columnSUM = [];
                foreach ( $p['columns'] as $column => $icolumn ) {
                    $sheet->setCellValueByColumnAndRow( $i, $row, $icolumn['title'] );
                    $p['columns'][$column]['index'] = $i;
                    $p['columns'][$column]['name'] = Coordinate::stringFromColumnIndex( $i );
                    $align = $sheet->getStyle( $p['columns'][$column]['name'] )->getAlignment();
                    $align->setWrapText( true );
                    $align->setHorizontal( Alignment::HORIZONTAL_LEFT );
                    $sheet->getColumnDimension( $p['columns'][$column]['name'] )->setAutoSize( true );
                    // style
                    $styleRow = $sheet->getStyle( $p['columns'][$column]['name'] . $row );
                    $styleRow->applyFromArray( $styleColumn );
                    $styleRow->getAlignment()->setHorizontal( Alignment::HORIZONTAL_LEFT );
                    $styleRow->getAlignment()->setVertical( Alignment::VERTICAL_CENTER );
                    /** Check for currency */
                    if ( !empty( $icolumn['format'] ) ) {
                        if ( $icolumn['format'] === 'currency' ) {
                            if ( null === $currencyStart ) {
                                $currencyStart = $p['columns'][$column]['index'];
                            }
                            $columnSUM[] = $p['columns'][$column]['name'];
                        }
                    }

                    $i++;
                }
                $row++;
                $startItem = $row;
                if ( sizeof( $p['items'] ) > 0 ) {
                    foreach ( $p['items'] as $item ) {
                        foreach ( $item as $itemName => $itemValue ) {
                            if ( !isset( $p['columns'][$itemName]['index'] ) ) {
                                continue;
                            }
                            $c = $p['columns'][$itemName];

                            if ( !empty( $c['format'] ) ) {
                                excel_format( $sheet, $c['format'], $c['name'] . $row, $itemValue );
                            } else {
                                $sheet->getStyleByColumnAndRow( $p['columns'][$itemName]['index'], $row )->getFont()->setSize( 9 );
                                $sheet->setCellValueByColumnAndRow( $p['columns'][$itemName]['index'], $row, $itemValue );
                            }
                        }
                        $row++;
                    }
                } else {
                    $mergeCellName = 'A' . $row . ':' . $maxColumnName . $row;
                    // Add some data
                    $sheet->mergeCells( $mergeCellName );
                    $sheet->getStyle( $mergeCellName )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_CENTER );
                    $sheet->getStyle( $mergeCellName )->getAlignment()->setVertical( Alignment::VERTICAL_CENTER );
                    $sheet->getCell( 'A' . $row )->setValue( 'No Data' );
                    $sheet->getStyle( 'A' . $row )->getFont()
                        ->setSize( 9 )
                        ->setItalic( true );
                    $row++;
                }
                $endItem = $row;
                if ( count( $columnSUM ) > 0 ) {
                    $currencyStartName = Coordinate::stringFromColumnIndex( $currencyStart - 1 );
                    $mergeCellName = 'A' . $row . ':' . $currencyStartName . $row;
                    $sheet->mergeCells( $mergeCellName );
                    $sheet->getStyle( $mergeCellName )->getAlignment()->setHorizontal( Alignment::HORIZONTAL_RIGHT );
                    $sheet->getStyle( 'A' . $row . ':' . $maxColumnName . $row )->applyFromArray( $styleColumn );
                    $sheet->getCell( 'A' . $row )->setValue( 'Total' );
                    foreach ( $columnSUM as $colSum ) {
                        excel_format( $sheet, 'currency', $colSum . $row, "=SUM($colSum$startItem:$colSum$endItem)" );
                    }
                }
            }
        }

        $this->extension = pathinfo( $this->filename, PATHINFO_EXTENSION );
        $writer = IOFactory::createWriter( $this->spreadsheet, ucfirst( $this->extension ) );
        $this->file = storage_path( $this->filename );
        $writer->save( $this->file );
        unset( $writer );

        return $this;
    }

    /**
     * @return Spreadsheet
     */
    public function getSpreadsheet()
    {
        if ( null === $this->spreadsheet ) {
            // Create new Spreadsheet object
            $this->spreadsheet = new Spreadsheet();
        }

        return $this->spreadsheet;
    }

    /**
     * @return string
     */
    public function getTitleDocument()
    {
        return $this->titleDocument;
    }

    /**
     * @param string $titleDocument
     *
     * @return GridExcel
     */
    public function setTitleDocument( $titleDocument )
    {
        $this->titleDocument = $titleDocument;

        return $this;
    }
}
