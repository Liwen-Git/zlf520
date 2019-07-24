<?php
/**
 * Created by PhpStorm.
 * User: 57458
 * Date: 2019/1/15
 * Time: 15:34
 */

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

define('UTF8_BOM',  chr(0xEF) . chr(0xBB) . chr(0xBF));
class CsvExporter
{
    public $file;

    /**
     * 下载数据为csv文件
     * @param Collection|array|Model|Builder|\Illuminate\Database\Query\Builder $data
     * @param string $filename
     * @param array $headers
     * @param callable $map
     */
    public static function download($data, $headers, $filename, $map = null)
    {
        header('Content-Encoding: UTF-8');
        header('Content-Type: application/vnd.ms-execl');
        header('Content-Disposition: attachment;filename="' . $filename . '.csv"');

        $exporter = new CsvExporter();
        $exporter->setFile('php://output')->export($data, $headers, $map);

        ob_flush();
        flush();
    }


    /**
     * 导出数据
     * @param Collection|array|Model|Builder|\Illuminate\Database\Query\Builder $data
     * @param array $headers
     * @param callable $map
     * @return CsvExporter
     */
    public function export($data, $headers = null, $map = null)
    {
        set_time_limit(0); // 导出时, 统一设置请求超时时间为0
        $this->writeBOM();
        $this->writeHeader($headers);
        // 如果
        if($data instanceof Model || $data instanceof Builder || $data instanceof \Illuminate\Database\Query\Builder){
            $data->chunk(500, function($list) use ($headers, $map){
                $this->writeBody($list, $headers, $map);
            });
        }else {
            $this->writeBody($data, $headers, $map);
        }
        return $this;
    }

    /**
     * 向csv数据中写数据
     * @param array $data
     * @param array $headers
     * @param callable $map
     * @return CsvExporter
     */
    public function writeBody($data, $headers = null, $map = null)
    {
        foreach ($data as $item) {
            if($map){
                $item = $map($item);
            }
            if($item instanceof Model){
                $item = $item->toArray();
            }
            $line = [];
            foreach ($headers as $key => $value) {
//                $line[$key] = mb_convert_encoding($item[$key], 'GB2312', 'UTF-8'); // 这里将UTF-8转为GBK编码
                $line[$key] = $item[$key];
            }
            $this->writeLine($line);
        }
        return $this;
    }

    /**
     * 根据编码给导出的文件写BOM头
     * @param string $charset
     */
    public function writeBOM($charset = 'utf-8')
    {
        if($charset == 'utf-8'){
            fwrite($this->file, UTF8_BOM);
        }
    }

    public function writeHeader($headers = null)
    {
//        foreach ($headers as $key => $value) {
//            $headers[$key] =  mb_convert_encoding($value, 'GB2312', 'UTF-8');
//        }
        $this->writeLine($headers);
        return $this;
    }

    public function writeLine($line)
    {
//        if(is_array($line) || $line instanceof Arrayable){
//            $line = implode(',', $line);
//        }
//        \Log::info('aaaaaaaaaa', ($line));
        fputcsv($this->file, $line);
        return $this;
    }

    public function setFile($filename)
    {
        $this->file = fopen($filename, 'a');
        return $this;
    }

}