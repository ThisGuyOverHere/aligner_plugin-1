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


        let mockFrom = {
            clean: null,
            raw: null,
            words: null,
            order: null,
            next: null

        };

        let mockToInverse = {
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
            console.log(tmpJob[inverse[log.type]].get(toIndex).toJS());
            console.log(tmpJob[inverse[log.type]].get(toIndex+1).toJS());
            mockFrom.order = tmpJob[inverse[log.type]].getIn([toIndex, 'order']) + (tmpJob[inverse[log.type]].getIn([toIndex + 1, 'order']) - tmpJob[inverse[log.type]].getIn([toIndex, 'order'])) / 2;
            mockFrom.next = tmpJob[inverse[log.type]].getIn([+toIndex + 1, 'order']);
            changes.push({
                type: inverse[log.type],
                action: 'create',
                rif_order: tmpJob[inverse[log.type]].getIn([toIndex + 1, 'order']),
                data: mockFrom
            });

            /******
             *  3 *
             ******/
            //cambio il next dell'elemento precedente al buco creato al punto 2
            changeData = tmpJob[inverse[log.type]].get(toIndex).toJS();
            changeData.next = mockFrom.order;

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
            mockToInverse.order = tmpJob[log.type].getIn([fromIndex, 'order']);
            mockToInverse.next = tmpJob[log.type].getIn([fromIndex, 'next']);

            changes.push({
                type: log.type,
                action: 'update',
                rif_order: tmpJob[log.type].getIn([fromIndex, 'order']),
                data: mockToInverse
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
};


export default ProjectActions;