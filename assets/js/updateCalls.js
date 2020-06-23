const getNewCalls = (action) => {
        fetch('/newcallsforuser', {
        })
        .then(function (response) {
            return response.text()
        }).then(function (html) {
            action(html);
        });
}

const initButtons = (modal) => {
    const buttons = document.getElementsByClassName('call-treatment-btn');
    const modalHtmlZone = document.getElementById('modal-content-call-treatment');
    for (let i=0; i<buttons.length; i++) {
        buttons[i].addEventListener('click', (e) => {
            e.preventDefault();
            const callId = buttons[i].dataset.call;
            getProcessForm(callId, (html) => {
                modalHtmlZone.innerHTML = html
                initializeSelects()

                const processBtn = document.getElementById('process-call-btn');
                const processForm        = document.getElementById('form-process');
                processForm.onsubmit = (e) => {
                    e.preventDefault();
                    const data   = new FormData(e.target);
                    const callId = processBtn.dataset.call;
                    const url    = `/call/process/${callId}/add`;
                    const params = {
                        body: data,
                        method: "post"
                    }

                    fetch(url, params)
                        .then(response => {
                            return response.json()
                        }).then(data => {
                        modal.close();
                        M.toast({html: 'Traitement enregistré', classes:'light-blue'})
                        console.log(data);
                        const target = document.getElementById('call-history-' + data.callId);
                        const targetHtml = '<span class="chip ' + data.colors.bgColor + ' black-text">' + data.colors.stepName + '</span>\n' +
                            '                            <b>\n' +
                            '                                ' + data.date + '\n' +
                            '                                à ' + data.time + '\n' +
                            '                            </b>';
                        target.innerHTML = targetHtml;
                    });
                }
            })
            modal.open();
        })
    }
}

const initTransferButtons = (modal) => {
    const transferButtons = document.getElementsByClassName('call-transfer-btn');
    const transferModalHtmlZone = document.getElementById('modal-content-call-transfer');
    for (let i=0; i<transferButtons.length; i++) {
        transferButtons[i].addEventListener('click', (e) => {
            e.preventDefault();
            const callId = transferButtons[i].dataset.call;
            getTransferForm(callId, (html) => {
                transferModalHtmlZone.innerHTML = html
                initializeSelects()

                const transferBtn = document.getElementById('transfer-call-btn');
                const form        = document.getElementById('form-transfer');
                form.onsubmit = (e) => {
                    e.preventDefault();
                    const data   = new FormData(e.target);
                    const callId = transferBtn.dataset.call;
                    const url    = `/call/process/${callId}/dotransfer`;
                    const params = {
                        body: data,
                        method: "post"
                    }

                    fetch(url, params)
                        .then(response => {
                            return response.json()
                        }).then(data => {
                        modal.close();
                        M.toast({html: 'Appel transféré', classes:'red'})
                        console.log(data.callId)
                        document.getElementById('call-' + data.callId).remove();
                    });
                }
            })
            modal.open();
        })
    }
}

const getProcessForm = (callId, action) => {

    fetch('/call/process/' + callId, {
        method      : 'GET',
        headers     : {
            'Content-Type': 'application/json'
        },
    })
        .then(function (response) {
            return response.text()
        }).then(function (html) {
        action(html);
    });
}

const getTransferForm = (callId, action) => {

    fetch('/call/process/' + callId + '/transfer', {
        method      : 'GET',
        headers     : {
            'Content-Type': 'application/json'
        },
    })
        .then(function (response) {
            return response.text()
        }).then(function (html) {
        action(html);
    });
}

const initializeSelects = () => {
    const selects = document.querySelectorAll('select');
    const instancesOfSelects = M.FormSelect.init(selects, {});
}


document.addEventListener('DOMContentLoaded', () => {
    const modalCallTreatment         = document.getElementById('modal-call-treatment');
    const modalCallTreatmentInstance = M.Modal.init(modalCallTreatment, {});
    initButtons(modalCallTreatmentInstance);

    const modalCallTransfer         = document.getElementById('modal-call-transfer');
    const modalCallTransferInstance = M.Modal.init(modalCallTransfer, {});
    initTransferButtons(modalCallTransferInstance);

    const checker = setInterval(()=> {
        getNewCalls(html => {
            if (html != ' ') {
                const listOfCallsZone = document.getElementById('list-calls-to-process')
                //TODO Insert row at the good place not a the end
                listOfCallsZone.innerHTML += html
                initButtons(modalCallTreatmentInstance);
            }
        })
    }, 15000);

})
