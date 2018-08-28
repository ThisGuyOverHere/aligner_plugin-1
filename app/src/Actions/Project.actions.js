import ProjectStore from "../Stores/Project.store";

let AppDispatcher = require('../Stores/AppDispatcher');
import ProjectConstants from '../Constants/Project.constants';
import {httpAlignJob} from "../HttpRequests/Alignment.http";
import env from "../Constants/Env.constants";
import {avgOrder} from "../Helpers/SegmentUtils.helper";


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
     * @param {String} log.type The type of segment: source or target
     * @param {Number} log.from The row's order of Drag action
     * @param {Number} log.to The row's order of Drop action
     */
    changeSegmentPosition: function (log) {


        let tmpJob = ProjectStore.job,
            changeData,
            changes = [],
            fromIndex = tmpJob[log.type].findIndex(i => i.get('order') === log.from),
            toIndex = tmpJob[log.type].findIndex(i => i.get('order') === log.to),
            mockFrom = Object.assign({}, env.segmentModel),
            mockToInverse = Object.assign({}, env.segmentModel);

        const inverse = {
                source: 'target',
                target: 'source'
            },
            toOrder = tmpJob[log.type].getIn([toIndex, 'order']),
            toNextOrder = tmpJob[log.type].getIn([+toIndex + 1, 'order']),
            toInverseOrder = tmpJob[inverse[log.type]].getIn([toIndex, 'order']),
            toNextInverseOrder = tmpJob[inverse[log.type]].getIn([+toIndex + 1, 'order']),
            fromOrder = tmpJob[log.type].getIn([fromIndex, 'order']),
            fromNextOrder = tmpJob[log.type].getIn([+fromIndex + 1, 'order']),
            fromInverseOrder = tmpJob[inverse[log.type]].getIn([fromIndex, 'order']),
            fromNextInverseOrder = tmpJob[inverse[log.type]].getIn([+fromIndex + 1, 'order']);


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

        //controllo se l'elemento in toIndex non sia vuoto, nel caso sia vuoto salto il punto 1 e 2 e 3
        //lo salto perchè quando si rimpiazza un buco non bisogna spostare l'elemento in posizione toIndex (i buchi si rimpiazzano e basta)
        if (changeData.clean !== null) {

            changeData.order = avgOrder(toOrder, toNextOrder);
            changeData.next = toNextOrder;
            changes.push({
                type: log.type,
                action: 'create',
                rif_order: toNextOrder,
                data: changeData
            });

            /******
             *  2 *
             ******/
            // creo un buco in corrispondenza dell'elemento spostato
            mockFrom.order = avgOrder(toInverseOrder, toNextInverseOrder);
            mockFrom.next = toNextInverseOrder;
            changes.push({
                type: inverse[log.type],
                action: 'create',
                rif_order: toNextInverseOrder,
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
                rif_order: toInverseOrder,
                data: changeData
            });
        }


        /******
         *  4 *
         ******/
        // sostituisco l'elemento draggato (fromIndex) con quello nella posizione toIndex
        changeData = tmpJob[log.type].get(fromIndex).toJS();
        changeData.order = toOrder;
        changeData.next = tmpJob[log.type].getIn([toIndex, 'next']);

        //se l'elemento del punto 1 è stato creato, il punto due avrà un next diverso
        //se invece c'era un buco nel punto 1 non cambia nulla, poichè non abbiamo creato nuovi elementi successivi
        if (changes.length > 0) {
            changeData.next = changes[0].data.order
        }
        changes.push({
            type: log.type,
            action: 'update',
            rif_order: toOrder,
            data: changeData
        });

        /******
         *  5 *
         ******/
        //se l'elemento opposto al fromIndex non è vuoto creo il buco altrimenti cancello entrambi gli elementi
        // buco / buco si annulla
        if (tmpJob[inverse[log.type]].getIn([fromIndex, 'clean'])) {
            mockToInverse.order = fromOrder;
            mockToInverse.next = tmpJob[log.type].getIn([fromIndex, 'next']);

            changes.push({
                type: log.type,
                action: 'update',
                rif_order: fromOrder,
                data: mockToInverse
            });
        } else {
            changes.push({
                type: log.type,
                action: 'delete',
                rif_order: fromOrder
            });
            changes.push({
                type: inverse[log.type],
                action: 'delete',
                rif_order: fromInverseOrder
            });
        }

        AppDispatcher.dispatch({
            actionType: ProjectConstants.CHANGE_SEGMENT_POSITION,
            changes: changes
        });
    },

    /**
     *
     * @param {Object} log A log of position and type of action
     * @param {Number} log.order The position where create a space
     * @param {String} log.type The type of segment: source or target
     */
    createSpaceSegment: function (log) {
        const tmpJob = ProjectStore.job,
            index = tmpJob[log.type].findIndex(i => i.get('order') === log.order),
            prevOrder = tmpJob[log.type].getIn([+index - 1, 'order']),
            inverse = {
                source: 'target',
                target: 'source'
            };
        let changes = [],
            mock = Object.assign({}, env.segmentModel);

        //aggiungo il buco
        mock.order = avgOrder(prevOrder, log.order);
        mock.next = log.order;
        changes.push({
            type: log.type,
            action: 'create',
            rif_order: log.order,
            data: mock
        });

        //cambio il next dell'elemento precedente al buco
        let changeData = tmpJob[log.type].get(+index - 1).toJS();
        changeData.next = mock.order;
        changes.push({
            type: log.type,
            action: 'update',
            rif_order: changeData.order,
            data: changeData
        });

        //aggiungo un buco a fine lista inversa
        let lastMock = Object.assign({}, env.segmentModel),
            lastSegment = tmpJob[inverse[log.type]].get(-1).toJS();

        lastMock.order = +lastSegment.order + env.orderElevation;
        lastSegment.next = lastMock.order;
        changes.push({
            type: inverse[log.type],
            action: 'update',
            rif_order: lastSegment.order,
            data: lastSegment
        });
        changes.push({
            type: inverse[log.type],
            action: 'push',
            data: lastMock
        });

        AppDispatcher.dispatch({
            actionType: ProjectConstants.CHANGE_SEGMENT_POSITION,
            changes: changes
        });

    }
};


export default ProjectActions;