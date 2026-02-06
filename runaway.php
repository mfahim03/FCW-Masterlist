<?php
include 'db.php';
include 'config/fetchEocRunaway.php';
session_start();

if (!isset($_SESSION['username']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: indexView.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Foreign Contract Worker</title>
    <link rel="icon" type="image/png" href="img/fcw2.png">
    <link rel="stylesheet" href="css/index.css">
    <link rel="stylesheet" href="css/runaway.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body style="background-image: url('img/bck.png'); background-size: cover;">
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <span class="alert-close" onclick="this.parentElement.remove()">&times;</span>
            <i class="fa-solid fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <span class="alert-close" onclick="this.parentElement.remove()">&times;</span>
            <i class="fa-solid fa-check-circle"></i>
            <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <div class="header">
        <a href="#" class="logout-link">
            <i class="fa-solid fa-right-from-bracket" style="font-size: medium;"></i>
        </a>
        <p>FCW EOC/Runaway</p>
    </div>

    <?php include 'model/navigationBar.php'; ?>

    <div class="content-wrapper">
        <div class="filters-container">
            <div class="search-container">
                <i class="fa-solid fa-search"></i>
                <input type="text" 
                       id="search" 
                       placeholder="Search by Name or Employee No"
                       autocomplete="off">
            </div>

            <div class="filter-pill">
                <label for="nationalityFilter">
                    <i class="fa-solid fa-globe"></i> Nationality:
                </label>
                <div class="select-wrapper">
                    <select id="nationalityFilter" onchange="applyFilters()">
                        <option value="all">All Nationalities</option>
                        <?php foreach ($nationalities as $nat): ?>
                            <option value="<?php echo htmlspecialchars($nat); ?>" 
                                <?php echo ($nationalityFilter == $nat) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($nat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fa-solid fa-chevron-down dropdown-arrow"></i>
                </div>
            </div>
            
            <div class="pill-toggle-container">
                <a href="?status=EOC" class="pill-btn <?php echo ($statusFilter == 'EOC') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-user-xmark"></i> EOC (<?php echo $total_eoc_records; ?>)
                </a>
                <a href="?status=RUNAWAY" class="pill-btn <?php echo ($statusFilter == 'RUNAWAY') ? 'active' : ''; ?>">
                    <i class="fa-solid fa-person-running"></i> Runaway (<?php echo $total_runaway_records; ?>)
                </a>
            </div>

            <button class="download-btn-pill" onclick="downloadExcel()">
                <i class="fa-regular fa-file-excel"></i>
                Download Excel
            </button>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Employee No</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Nationality</th>
                    <th>Status</th>
                    <th>Flight Date</th>
                    <th>Remarks</th>
                    <th>Employee Details</th>
                </tr>
            </thead>
            <tbody id="tableBody">
                <?php foreach ($allRecords as $record): 
                    
                    
                    $flightDate = $record['FlightDate'];
                    if ($flightDate instanceof DateTime) {
                        $flightDateFormatted = $flightDate->format('Y-m-d');
                        $flightDateDisplay = $flightDate->format('d-m-Y');
                    } else {
                        $flightDateFormatted = '';
                        $flightDateDisplay = 'Not set';
                    }
                    
                    $statusClass = strtolower($record['Status']) == 'eoc' ? 'status-eoc' : 'status-runaway';
                    $empNo = htmlspecialchars($record['Employee#']);
                    $remarksPDF = $record['RemarksPDF'] ?? '';
                ?>
                <tr class="employee-row" 
                    data-empno="<?php echo strtolower($empNo); ?>"
                    data-name="<?php echo strtolower(htmlspecialchars($record['Name'])); ?>">
                    <td><?php echo $empNo; ?></td>
                    <td><?php echo htmlspecialchars($record['Name']); ?></td>
                    <td><?php echo htmlspecialchars($record['Department'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($record['Nationality'] ?? 'N/A'); ?></td>
                    <td><span class="<?php echo $statusClass; ?>"><?php echo htmlspecialchars($record['Status']); ?></span></td>
                    <td class="flight-date-cell">
                        <div class="flight-date-display" id="display-<?php echo $empNo; ?>">
                            <span class="flight-date-text <?php echo empty($flightDateFormatted) ? 'empty' : ''; ?>">
                                <?php echo htmlspecialchars($flightDateDisplay); ?>
                            </span>
                            <button class="edit-flight-date-btn" 
                                    onclick="editFlightDate('<?php echo $empNo; ?>', '<?php echo $flightDateFormatted; ?>')"
                                    title="Edit Flight Date">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                        </div>
                        <div class="flight-date-edit-form" id="edit-<?php echo $empNo; ?>">
                            <input type="date" 
                                   class="flight-date-input" 
                                   id="input-<?php echo $empNo; ?>" 
                                   value="<?php echo $flightDateFormatted; ?>">
                            <button class="save-flight-date-btn" 
                                    onclick="saveFlightDate('<?php echo $empNo; ?>')"
                                    title="Save">
                                <i class="fa-solid fa-check"></i>
                            </button>
                            <button class="cancel-flight-date-btn" 
                                    onclick="cancelEditFlightDate('<?php echo $empNo; ?>')"
                                    title="Cancel">
                                <i class="fa-solid fa-times"></i>
                            </button>
                        </div>
                    </td>
                    <td class="remarks-cell">
                        <div class="remarks-display">
                            <div class="remarks-actions">
                                <button class="upload-remarks-btn" 
                                        onclick="openRemarksModal('<?php echo $empNo; ?>')"
                                        title="Upload Remarks PDF">
                                    <i class="fa-solid fa-upload"></i>
                                </button>
                                <?php if (!empty($remarksPDF)): ?>
                                <a href="<?php echo htmlspecialchars($remarksPDF); ?>" 
                                   target="_blank" 
                                   class="view-remarks-pdf-btn"
                                   title="View Remarks PDF">
                                    <i class="fa-solid fa-file-pdf"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td><a href="runawayDetail.php?id=<?php echo urlencode($record['Employee#']); ?>" class="view-detail-btn" title="View Full Details"><i class="fa-solid fa-eye"></i></a></td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($allRecords)): ?>
                <tr>
                    <td colspan="9" class="no-data">
                        <?php echo $statusFilter === 'EOC' ? 'No EOC records found.' : 'No Runaway records found.'; ?>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination" id="paginationContainer">
            <a href="#" id="firstPage" onclick="changePage(1); return false;">First</a>
            <a href="#" id="prevPage" onclick="prevPage(); return false;"><i class='fa-solid fa-chevron-left'></i></a>
            
            <span id="pageNumbers"></span>
            
            <a href="#" id="nextPage" onclick="nextPage(); return false;"><i class='fa-solid fa-chevron-right'></i></a>
            <a href="#" id="lastPage" onclick="lastPage(); return false;">Last</a>
        </div>
        
        <div class="page-info">
            Showing <span id="showingStart">0</span> - <span id="showingEnd">0</span> of <span id="totalRecordsDisplay">0</span> records
        </div>
    </div>

    <!-- Remarks Upload Modal -->
    <div id="remarksModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fa-solid fa-file-pdf"></i> Upload Remarks PDF</h3>
                <button class="modal-close" onclick="closeRemarksModal()">&times;</button>
            </div>
            <form id="remarksUploadForm" enctype="multipart/form-data">
                <input type="hidden" id="modalEmpNo" name="empNo">
                <div class="upload-section">
                    <label for="remarksPDF">Select PDF File:</label>
                    <div class="file-input-wrapper">
                        <input type="file" id="remarksPDF" name="remarksPDF" accept=".pdf" onchange="updateFileLabel()">
                        <label for="remarksPDF" class="file-input-label" id="fileLabel">
                            <i class="fa-solid fa-cloud-arrow-up"></i>
                            <span id="fileLabelText">Choose PDF file or drag here</span>
                        </label>
                    </div>
                </div>
                <div class="modal-actions">
                    <button type="button" class="modal-btn modal-btn-secondary" onclick="closeRemarksModal()">
                        <i class="fa-solid fa-times"></i>
                        Cancel
                    </button>
                    <button type="submit" class="modal-btn modal-btn-primary">
                        <i class="fa-solid fa-upload"></i>
                        Upload
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'model/footer.php'; ?>
    
    <script src="js/eoc.js"></script>
    <script src="js/employeeInfo.js"></script>
    <script src="js/flightDate.js"></script>
</body>
</html>