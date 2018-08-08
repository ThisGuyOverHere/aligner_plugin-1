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
    /**
     *
     * @param {Number} jobID The Job ID of current project
     * @param {String} jobPassword The password of current Job ID
     * @param {Number} algorithm The version of algorithm for alignment
     */
    getSegments: function (jobID, jobPassword, algorithm = null) {
        httpAlignJob(jobID, algorithm).then(response => {
            AppDispatcher.dispatch({
                actionType: ProjectConstants.STORE_SEGMENTS,
                segments: response.data
            });
        });
    },


    /**
     *
     * @param {Object} log A log of move action from frontend
     * @param {String} log.type The type of segment: Source or Target
     * @param {Number} log.from The row's order of Drag action
     * @param {Number} log.to The row's order of Drop action
     */
    changeSegmentPosition: function (log) {


        let tmpJob = ProjectStore.job,
            changeData,
            changes = [];

        let fromIndex = tmpJob[log.type].findIndex(i => i.get('order') === log.from);
        let toIndex = tmpJob[log.type].findIndex(i => i.get('order') === log.to);


        let mock = {
            clean: null,
            raw: null,
            words: null,
            order: null,
            next: null

        };

        const inverse = {
            source: 'target',
            target: 'source'
        };


        /*
        * 1. Creo elemento successivo all'arrivo con il contenuto di quello di arrivo (prima dello spostamento)
        * 2. Creo elemento in corrispondenza del type opposto del punto 1
        * 3. Cambio il next dell'elemento precedente al punto 2
        * 4. Aggiorno elemento di arrivo mettendoci il segmento draggato
        * 5. Aggiorno elemento di partenza creando un buco
        * (se c'è un buco dall'altra parte non lo creo, ma cancello entrambi i segmenti)
        * */


        /******
         *  1 *
         ******/

        changeData = tmpJob[log.type].get(toIndex).toJS();

        //controllo se l'elemento in toIndex non sia vuoto, nel caso sia vuoto salto il punto 1 e 2
        //lo salto perchè quando si rimpiazza un buco non bisogna spostare l'elemento in posizione toIndex (i buchi si rimpiazzano e basta)
        if (changeData.clean !== null) {
            changeData.order = tmpJob[log.type].getIn([toIndex, 'order']) + (tmpJob[log.type].getIn([toIndex + 1, 'order']) - tmpJob[log.type].getIn([toIndex, 'order'])) / 2;
            changeData.next = tmpJob[log.type].getIn([+toIndex + 1, 'order']);
            changes.push({
                type: log.type,
                action: 'create',
                rif_order: tmpJob[log.type].getIn([toIndex + 1, 'order']),
                data: changeData
            });

            /******
             *  2 *
             ******/
            // creo un buco in corrispondenza dell'elemento spostato
            mock.order = tmpJob[inverse[log.type]].getIn([toIndex, 'order']) + (tmpJob[inverse[log.type]].getIn([toIndex + 1, 'order']) - tmpJob[inverse[log.type]].getIn([toIndex, 'order'])) / 2;
            mock.next = tmpJob[inverse[log.type]].getIn([+toIndex + 1, 'order']);
            changes.push({
                type: inverse[log.type],
                action: 'create',
                rif_order: tmpJob[inverse[log.type]].getIn([toIndex + 1, 'order']),
                data: mock
            });

            /******
             *  3 *
             ******/
            //cambio il next dell'elemento precedente al buco creato al punto 2
            changeData = tmpJob[inverse[log.type]].get(toIndex).toJS();
            changeData.next = mock.order;

            changes.push({
                type: inverse[log.type],
                action: 'update',
                rif_order: tmpJob[inverse[log.type]].getIn([toIndex, 'order']),
                data: changeData
            });
        }


        /******
         *  4 *
         ******/
        // sostituisco l'elemento draggato (fromIndex) con quello nella posizione toIndex
        changeData = tmpJob[log.type].get(fromIndex).toJS();
        changeData.order = tmpJob[log.type].getIn([toIndex, 'order']);

        //se l'elemento del punto 1 è stato creato, il punto due avrà un next diverso
        //se invece c'era un buco nel punto 1 non cambia nulla, poichè non abbiamo creato nuovi elementi successivi
        if (changes.length > 0) {
            changeData.next = changes[0].data.order
        }
        changes.push({
            type: log.type,
            action: 'update',
            rif_order: tmpJob[log.type].getIn([toIndex, 'order']),
            data: changeData
        });


        /******
         *  5 *
         ******/
        //se l'elemento opposto al fromIndex non è vuoto creo il buco altrimenti cancello entrambi gli elementi
        // buco / buco si annulla
        if(tmpJob[inverse[log.type]].getIn([fromIndex, 'clean'])){
            mock.order = tmpJob[log.type].getIn([fromIndex, 'order']);
            mock.next = tmpJob[log.type].getIn([fromIndex, 'next']);

            changes.push({
                type: log.type,
                action: 'update',
                rif_order: tmpJob[log.type].getIn([fromIndex, 'order']),
                data: mock
            });
        }else{
            changes.push({
                type: log.type,
                action: 'delete',
                rif_order: tmpJob[log.type].getIn([fromIndex, 'order'])
            });
            changes.push({
                type: inverse[log.type],
                action: 'delete',
                rif_order: tmpJob[inverse[log.type]].getIn([fromIndex, 'order'])
            });
        }



        console.log(changes);
        AppDispatcher.dispatch({
            actionType: ProjectConstants.CHANGE_SEGMENT_POSITION,
            changes: changes
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
    /*changeSegmentPosition: function (log) {
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

        /!*Verifico se l'elemento di partenza era orfano, in caso invece di fare update faccio delite, poichè potrei partire
        con Pieno:null, se sposto Pieno rimane null:null e quindi cancello invece di update.
         *!/

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
    },*/
};


export default ProjectActions;