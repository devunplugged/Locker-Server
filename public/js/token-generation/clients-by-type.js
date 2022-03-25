
document.getElementById("client-type-select").addEventListener("change", (event) => {

    var clientsContainer = document.getElementById("clients-container");

    if(!event.target.value){
        clientsContainer.classList.add("d-none");
        return;
    }

    var dataToSend = { 'type': event.target.value };

    ajaxCall("/ajax/get-clients-by-type", dataToSend).then(
        data => {
            clientsContainer.classList.remove("d-none");
            data = JSON.parse(data);
            clearClientsContainer();
            document.getElementById("clients-container").appendChild(buildClientsSelect(data.clients));
        },
        error => {
            alert(error);
        }
    );
    
});

function clearClientsContainer(){
    document.getElementById("clients-container").innerText = '';
}

function buildClientsSelect(clients){

    var clientsSelect = document.createElement("select");
    clientsSelect.classList.add("form-select");

    for( const key in clients){
        var option = document.createElement("option");
        option.value = clients[key].id_hash;
        option.innerText = clients[key].name;
        clientsSelect.appendChild(option);
    }

    return clientsSelect;
}