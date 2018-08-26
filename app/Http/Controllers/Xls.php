<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * @see https://phpspreadsheet.readthedocs.io/en/develop/
 */
class Xls extends Controller
{
    /** This is the list of all columns in which data can be stored */
    const COLUMNS = ['A', 'B', 'C', 'D'];

    /** The list of all regex validations */
    const VALIDATE_RULES = [
        '/^\d+$/',
        '/^.+$/',
        '/^\d\d\d\d-\d\d-\d\d \d\d:\d\d:\d\d$/',
        '/^[\d\.]+$/',
    ];

    /** This is the number which data will be readed */
    const DATA_FIRST_ROW = 6;

    /** This is the MAX counter of empty rows (using in stopping loop) */
    const MAXIMUM_EMPTY_CELLS = 10;

    /** @var int This variable is neccessary for loop */
    protected $currentRow = self::DATA_FIRST_ROW;

    /** @var int How many empty rows already been readed? */
    protected $emptyRows = 0;

    /** @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet */
    protected $sheet = null;

    protected $notValidData = [];

    protected $validData = [];

    /**
     * This method will be executed when user sent xlsx file
     * Firstly we should parse it and save issues in this process
     * If there is any issue - just save it to an array and print later
     * After reading ~10 empty rows (data finished) - showing result to the user
     * 1) If there are no errors - let's do another step - save to the DB e.t.c.!
     * 2) If there is error - redirects user to 'fix issues' page
     *
     * @param Request $request
     * @return void
     */
    public function upload(Request $request)
    {
        $file = $request->file('xls-document');
        $spreadsheet = IOFactory::load($file->path());
        $this->sheet = $spreadsheet->getAllSheets()[0]; // this is the 'pages/lists'. and we can take any you wish

        while ($this->emptyRows < self::MAXIMUM_EMPTY_CELLS) {
            if ($this->readRow()) {
                $this->emptyRows = 0;
            } else {
                $this->emptyRows++;
            }
            $this->currentRow++;
        }

        // now we have valid data in $this->validData
        // and non - valid data in $this->notValidData
        if (count($this->notValidData)) {
            // there are any errors - just use another view and show to user UI for fix it
            return redirect('/upload/fix-data')
                ->with('validData', $this->validData)
                ->with('notValidData', $this->notValidData)
                ->with('maxColumns', count(self::COLUMNS));
        }

        return redirect()->back()->with('success');
    }

    public function fix(Request $request)
    {
        foreach ($request->data as $row => $data) {
            foreach (self::COLUMNS as $i => $sym) {
                $value = $data[$sym];
                if (!preg_match(self::VALIDATE_RULES[$i], $value)) {
                    $this->notValidData[$row][$sym] = $value;
                    continue;
                }
                $this->validData[$row][$sym] = $value;
            }
        }
        if (count($this->notValidData)) {
            return redirect('/upload/fix-data')
                ->with('validData', $this->validData)
                ->with('notValidData', $this->notValidData)
                ->with('maxColumns', count(self::COLUMNS));
        }
        return redirect('/')->with('success', 1);
    }

    /**
     * Reads, validates row
     * If there is no data - should be returned false
     *
     * @return boolean
     */
    protected function readRow(): bool
    {
        if (!$this->sheet->getCell(self::COLUMNS[0] . $this->currentRow)->getValue()) {
            // if empty row - we don't need to do anything here
            return false;
        }
        // okey, data exist
        // here we should validate it
        // save it
        $notValidatedCells = 0;
        foreach (self::COLUMNS as $i => $columnSymbol) {
            // reads cell and validate it
            $cell = $this->sheet->getCell($columnSymbol . $this->currentRow);
            if (!preg_match(self::VALIDATE_RULES[$i], $cell->getFormattedValue())) {
                // fck, bad data
                $notValidatedCells++;
                $this->notValidData[$this->currentRow][$columnSymbol] = $cell->getFormattedValue();
                continue;
            }
            // here the good data - u can do with it whatever u want
            $this->validData[$this->currentRow][$columnSymbol] = $cell->getFormattedValue();
        }
        return true;
    }
}
