import ProjectStore from "../Stores/Project.store";

let AppDispatcher = require('../Stores/AppDispatcher');
import ProjectConstants from '../Constants/Project.constants';
import {httpAlignJob} from "../HttpRequests/Alignment.http";


let ProjectActions = {
    /**
     *
     * @param {Number} jobID The Job ID of current project
     */
    setJobID: function (jobID) {
        AppDispatcher.dispatch({
            actionType: ProjectConstants.SET_JOB_ID,
            jobID: jobID
        });
    },
    getRows: function (jobID,jobPassword) {

         httpAlignJob(jobID).then(response =>{
            console.log(response.data);
             AppDispatcher.dispatch({
                 actionType: ProjectConstants.GET_ROWS,
                 rows: response.data
             });
         });

        AppDispatcher.dispatch({
            actionType: ProjectConstants.GET_ROWS,
            rows: rows
        });
    },
    /*getRows: function () {

        let italian = "Manuale utente di iPhone\n" +
            /!*"Consulta il manuale utente prima di utilizzare iPhone. \n" +
            "Vai all’indirizzo help.apple.com/ iphone. \n" +
            "Puoi consultare il manuale utente su iPhone tramite il segnalibro corrispondente in Safari. \n" +*!/
            "In alternativa, puoi scaricare il manuale utente da iBooks Store (dove disponibile). Consulta il manuale utente prima di utilizzare iPhone. \nPuoi consultare il manuale utente su iPhone tramite il segnalibro corrispondente in Safari. \\n" +
            "Conserva la documentazione per consultazione futura.\n" +
            "Sicurezza e utilizzo\n" +
            "Consulta “Sicurezza, utilizzo e supporto” nel Manuale utente di iPhone.\n" +
            "Esposizione a radiofrequenza\n" +
            "Su iPhone, vai in Impostazioni > Generali > Info > Note legali > Esposizione a RF. \n" +
            "Oppure vai sul sito: www.apple.com/legal/rfexposure.\n" +
            "Batteria\n" +
            "Non tentare di sostituire la batteria di iPhone autonomamente perché potrebbe essere danneggiata e ciò potrebbe causare surriscaldamenti, incendi e lesioni. \n" +
            "La batteria agli ioni di litio di iPhone deve essere riparata o riciclata da Apple o da un provider di\n" +
            "servizi autorizzato e deve essere riciclata o smaltita separatamente dai rifiuti domestici. \n" +
            "Smaltisci le batterie in conformità con le leggi e le linee guida ambientali locali. \n" +
            "Per informazioni sull’assistenza e il riciclo della batteria agli ioni di litio di Apple, consulta www.apple.com/it/batteries/service-and-recycling.\n" +
            "Compatibilità con gli apparecchi acustici (HAC)\n" +
            "Vai all’indirizzo support.apple.com/it-it/HT202186 o consulta “Apparecchi acustici” sul\n" +
            "Manuale utente di iPhone.\n" +
            "Evitare danni all’udito\n" +
            "Per evitare possibili danni all’udito, evita l’ascolto a livelli di volume elevati per lunghi periodi di tempo. ",
            english = "iPhone User Guide\n" +
                "Review the user guide before using iPhone. \n" +
                "Go to help.apple.com/iphone. \n" +
                "To view the user guide on iPhone, use the Safari bookmark. \n" +
                "Or download the user guide from the iBooks Store (where available). \n" +
                "Retain documentation for future reference.\n" +
                "Safety and Handling\n" +
                "See “Safety, handling, and support” in the iPhone User Guide.\n" +
                "Exposure to Radio Frequency\n" +
                "On iPhone, go to Settings > General > About > Legal > RF Exposure. \n" +
                "Or go to www.apple.com/legal/rfexposure.\n" +
                "Battery\n" +
                "Don’t attempt to replace the iPhone battery yourself—you may damage the battery, which could cause overheating, fire, and injury. \n" +
                "The lithium-ion battery in your iPhone should be serviced or recycled by Apple or an authorized service provider, and must be recycled or disposed of separately from household waste. \n" +
                "Dispose of batteries according to your local environmental laws and guidelines. \n" +
                "For information about Apple lithium-ion batteries and battery service and recycling, go to www.apple.com/batteries/ service-and-recycling.\n" +
                "Hearing Aid Compatibility (HAC)",
            rows = [];
        italian = italian.split('\n');
        english = english.split('\n');

        if(italian.length >= english.length){
            italian.map((e,i)=>{
                rows.push({
                    order: i*1000000000,
                    source: {
                        content: e
                    },
                    target: {
                        content: english[i] ? english[i] : null
                    },
                    next: i<italian.length-1 ? (i+1)*1000000000 : null,
                });
            });
        } else{
            english.map((e,i)=>{
                rows.push({
                    order: i*1000000000,
                    source: {
                        content: italian[i] ? italian[i] : null
                    },
                    target: {
                        content: e
                    },
                    next: i<english.length-1 ? (i+1)*1000000000 : null,
                });
            });
        }

        AppDispatcher.dispatch({
            actionType: ProjectConstants.GET_ROWS,
            rows: rows
        });
    },*/
    /**
     *
     * @param {Object} log A log of move action from frontend
     * @param {String} log.type The type of segment: Source or Target
     * @param {Number} log.from The row's order of Drag action
     * @param {Number} log.to The row's order of Drop action
     */
    changeSegmentPosition: function (log) {
        let tmpRows = ProjectStore.project,
            changes = [];

        let fromIndex = tmpRows.findIndex(i => i.get('order') === log.from);
        let toIndex = tmpRows.findIndex(i => i.get('order') === log.to);


        let mock = {
            order: null,
            source: {
                content: null
            },
            target: {
                content: null
            },
            next: null,
        };

        if (tmpRows.getIn([toIndex + 1, 'order']) >= 0) {
            //se esiste l'elemento successivo alla posizione di arrivo, il nuovo elemento si posiziona in mezzo
            mock.order = tmpRows.getIn([toIndex, 'order']) + (tmpRows.getIn([toIndex + 1, 'order']) - tmpRows.getIn([toIndex, 'order'])) / 2;
            mock.next = tmpRows.getIn([+toIndex + 1, 'order']);
        } else {
            //se non esiste l'elemento successivo alla posizione di arrivo, si mette alla fine.
            mock.order = (tmpRows.size + 1) * 1000000000;
            mock.next = null;
        }

        mock[log.type].content = tmpRows.getIn([toIndex, log.type, 'content']);

        tmpRows = tmpRows.setIn([toIndex, log.type, 'content'], tmpRows.getIn([fromIndex, log.type, 'content']));
        tmpRows = tmpRows.setIn([fromIndex, log.type, 'content'], null);
        tmpRows = tmpRows.setIn([toIndex, 'next'], mock.order);

        //aggiorno la riga di arrivo
        changes.push({
            action: 'update',
            rif_order: tmpRows.getIn([toIndex, 'order']),
            data: tmpRows.get(toIndex).toJS()
        });

        //creo la riga solo se uno dei due segmenti non è null
        //perchè potrebbero essere entrambi null? se trascino un orfano dentro un buco mi ritrovo con la riga da
        //dover creare null:null e quindi non la creo.
        if (mock.source.content !== null || mock.target.content !== null) {
            changes.push({
                action: 'create',
                rif_order: tmpRows.getIn([toIndex + 1, 'order']),
                data: mock
            });
        }

        /*Verifico se l'elemento di partenza era orfano, in caso invece di fare update faccio delite, poichè potrei partire
        con Pieno:null, se sposto Pieno rimane null:null e quindi cancello invece di update.
         */

        if (tmpRows.getIn([fromIndex, 'source', 'content']) === null
            && tmpRows.getIn([fromIndex, 'target', 'content']) === null) {
            changes.push({
                action: 'delete',
                rif_order: tmpRows.getIn([fromIndex, 'order']),
                data: null
            });
        } else {
            changes.push({
                action: 'update',
                rif_order: tmpRows.getIn([fromIndex, 'order']),
                data: tmpRows.get(fromIndex).toJS()
            });
        }

        AppDispatcher.dispatch({
            actionType: ProjectConstants.CHANGE_SEGMENT_POSITION,
            rows: changes
        });
    },
};


export default ProjectActions;