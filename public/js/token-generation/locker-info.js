getLockerDataInInterval();


function getLockerDataInInterval(){
    setInterval(getLockerInfo, 1000);
}

function getLockerInfo(){

    var lockerId = document.getElementById("lockerId").value;
    var dataToSend = {};

    //http://172.16.16.128/codeigniter4/auth-test
    ajaxCall("http://172.16.16.128/codeigniter4/auth-test/public/ajax/get-locker-info/" + lockerId, dataToSend).then(
        data => {
            console.log("response data:");
            console.log(data);
            data = JSON.parse(data);
            drawCells(data.info);
        },
        error => {
            alert(error);
        }
    );
}
function drawCells(cellsData){
    var table = document.createElement("table");
    table.classList.add("table");
    table.classList.add("table-dark");
    table.classList.add("table-striped");
    table.classList.add("table-hover");

    cellsData.forEach(cellData => {
        var row = document.createElement("tr");

        var cell = document.createElement("td");
        cell.innerHTML = cellData.cell_id;
        row.appendChild(cell);

        cell = document.createElement("td");
        cell.innerHTML = cellData.cell_sort_id;
        row.appendChild(cell);

        cell = document.createElement("td");
        cell.innerHTML = cellData.size;
        row.appendChild(cell);

        cell = document.createElement("td");
        cell.innerHTML = cellData.cell_status;
        row.appendChild(cell);

        cell = document.createElement("td");
        cell.innerHTML = cellData.id;
        row.appendChild(cell);

        cell = document.createElement("td");
        var openCellButton = document.createElement("button");
        openCellButton.innerHTML = "Otwórz";
        openCellButton.dataset.cellId = cellData.cell_id;
        cell.appendChild(openCellButton);
        row.appendChild(cell);

        table.appendChild(row);
    });

    document.getElementById("cells-table-container").innerHTML = "";
    document.getElementById("cells-table-container").appendChild(table);
}

function drawTasks(tasksData){
    var table = document.createElement("table");
    console.log("cellsData");
    console.log(cellsData);
    cellsData.forEach(cellData => {
        var row = document.createElement("tr");

        var cell = document.createElement("td");
        cell.innerHTML = cellData.cell_id;
        row.appendChild(cell);

        cell = document.createElement("td");
        cell.innerHTML = cellData.cell_sort_id;
        row.appendChild(cell);

        cell = document.createElement("td");
        cell.innerHTML = cellData.size;
        row.appendChild(cell);

        cell = document.createElement("td");
        cell.innerHTML = cellData.cell_status;
        row.appendChild(cell);

        cell = document.createElement("td");
        cell.innerHTML = cellData.id;
        row.appendChild(cell);

        table.appendChild(row);
    });

    document.getElementById("cells-table-container").innerHTML = "";
    document.getElementById("cells-table-container").appendChild(table);
}