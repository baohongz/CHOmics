<?php
// When escapeshellarg() was stripping my non-ASCII characters from a UTF-8 string, adding the following fixed the problem:

setlocale(LC_CTYPE, "en_US.UTF-8");

/**
 * Linux Control Class
 *
 * @author Liyu Qin
 */


class bxaf_linux{


	/**
	 * Progress ID
	 *
	 * @var string
	 */
	protected $process_id;


	/**
	 * Working directory
	 *
	 * @var string
	 */
	protected $working_dir;


	/**
	 * Define working directory
	 *
	 * @param string $dir
	 */
	public function __construct($dir) {
		chdir($dir);
		$this->working_dir = $dir;
	}



	/**
	 * Change working directory
	 *
	 * @param string $dir
	 */
	public function setdir($dir) {
		chdir($dir);
		$this->working_dir = $dir;
	}



	/**
	 * Execute command in background
	 *
	 * Return process ID
	 *
	 * @return integer
	 */
	public function execute($Command, $outputfile = '', $logfile = '', $Priority = 19) {
        $Priority = intval($Priority);
        if ($Priority <= 0 || $Priority > 19)
            $Priority = 19;
        if ($outputfile == '')
            $outputfile = '/tmp/temp.out';
        if ($logfile == '')
            $logfile = '/tmp/temp.log';
        $c = "nohup nice -n $Priority $Command 1> $outputfile 2> $logfile";
// $c = 'wget ftp://ftp-trace.ncbi.nlm.nih.gov/sra/sra-instant/reads/ByExp/sra/SRX/SRX149/SRX149611/SRR500879/SRR500879.sra';
// echo "$c<BR>";
        return shell_exec("$c & echo $!");
    }



	/**
	 * Check status
	 *
	 * Return whether the process is running
	 *
	 * @return boolean
	 */
	public function check_status($process_id) {
        $PID = intval($process_id);
        exec("ps $PID", $ProcessState);
        return (count($ProcessState) >= 2);
    }





	/**
	 * Kill process
	 *
	 * Return boolean whether the process has been killed
	 *
	 * @return boolean
	 */
	public function kill_process($PID){
        $PID = intval($PID);
        passthru("kill -9 $PID", $return);
        return $return;
    }



	/**
	 * Get Child Process
	 *
	 * Return an array of all child process and their parent
	 *
	 * @return array
	 */
	public function get_child_processes($PID){
        $pstree    = shell_exec("pstree -plA " . intval($PID));
        $pstree    = explode("\n", trim($pstree));
        $processes = array();
        foreach ($pstree as $n => $row) {
            for ($i = 0; $i < strlen($row); $i++) {
                if ($row[$i] == ' ')
                    $pstree[$n][$i] = $pstree[$n - 1][$i];
            }
            $pstree[$n] = preg_replace("/[\|\+\`]/", "-", $pstree[$n]);
            $cols       = explode("---", $pstree[$n]);
            for ($j = 1; $j < count($cols); $j++) {
                $pp = $cols[$j - 1];
                list($pp_name, $ppid) = explode("(", $pp);
                $ppid = str_replace(')', '', $ppid);
                $p    = $cols[$j];
                list($p_name, $pid) = explode("(", $p);
                $pid                       = str_replace(')', '', $pid);
                $processes['Names'][$ppid] = $pp_name;
                $processes['Names'][$pid]  = $p_name;
                $processes['List'][$pid]   = $ppid;
            }
        }
        return $processes;
    }


	/**
	 * Kill All Child Processes
	 *
	 * Return an array of errors for each process ID
	 *
	 * @param integer $PID
	 * @return array
	 */
	public function kill_child_processes($PID)
    {
        $processes = $this->get_child_processes(intval($PID));
        $errors    = array();
        foreach ($processes['Names'] as $id => $name) {
            $return = $this->kill_process($id);
            if ($return > 0)
                $errors[$id] = $name;
        }
        return $errors;
    }


}


?>