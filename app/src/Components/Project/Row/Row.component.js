import React, {Component} from 'react';
import {ItemTypes} from '../../../Constants/Draggable.constants';
import {DropTarget} from 'react-dnd';
import ProjectActions from "../../../Actions/Project.actions";
import PropTypes from "prop-types";

const RowTarget = {
    canDrop(props, monitor) {
        const from = monitor.getItem(),
            types = {
                'source': 0,
                'target': 1
            };

        if (from.segment.order !== props.children[types[from.type]].props.segment.order) {
            return true
        }
        return false
    },
    drop(props, monitor) {
        const from = monitor.getItem(),
            types = {
                'source': 0,
                'target': 1
            },
            inverse = {
                'source': 1,
                'target': 0
            },
            log = {
                type: from.type,
                from: from.segment.order,
                to: props.children[types[from.type]].props.segment.order
            };
        ProjectActions.changeSegmentPosition(log);

        //send type and order of inverse segment in drop position.
        props.setAnimatedRow(props.children[inverse[from.type]].props.type,props.children[inverse[from.type]].props.segment.order);
    }
};

function collect(connect, monitor) {
    return {
        connectDropTarget: connect.dropTarget(),
        isOver: monitor.isOver(),
        canDrop: monitor.canDrop(),
        dragEl: monitor.getItem()
    }
}

class RowComponent extends Component {

    static propTypes = {
        index: PropTypes.number.isRequired,
        animate: PropTypes.bool,
        setAnimatedRow: PropTypes.func,
        row: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);

        this.state = {};
    }

    static getDerivedStateFromProps(props, state) {

        return null;
    }

    shouldComponentUpdate(nextProps, nextState) {

        return true;
    }

    getSnapshotBeforeUpdate(prevProps, prevState) {

        return null;
    }

    componentDidUpdate(prevProps, prevState, snapshot) {
        if(this.props.animate){
            setTimeout(()=>{this.props.setAnimatedRow(null,null)},500)
        }
    }

    render() {
        let rowClass = ['project-row'];
        let columnsClass = {
            source: ['eight','wide','column','columnSegment'],
            target: ['eight','wide','column','columnSegment'],
        };
        const {connectDropTarget, isOver, canDrop, dragEl, animate} = this.props;

        //todo: migliorare l'hover, attualmente abbiamo adattato il vecchio comportamento a 2 righe invece che 1
        const dragElType  = dragEl ? dragEl.type : undefined;
        if(isOver && dragElType && canDrop || animate){
            columnsClass.source.push('dropColumn');
            columnsClass.target.push('dropColumn');
        }
        if(animate){
            columnsClass.source.push('dropped');
            columnsClass.target.push('dropped');
        }
        return connectDropTarget(
            <div className={rowClass.join(' ')}>
                <div className="ui grid middle aligned">
                    <div className="one wide column center aligned">
                        {this.props.index}
                    </div>
                    <div className="fifteen wide column center aligned">
                        <div className="ui grid top aligned">
                            <div className={columnsClass.source.join(' ')}>
                                {this.props.children[0]}
                            </div>
                            <div className={columnsClass.target.join(' ')}>
                                {this.props.children[1]}
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        );
    }

    componentDidCatch() {

    }

    componentDidMount() {

    }

    componentWillUnmount() {
    }
}

export default DropTarget(ItemTypes.ITEM, RowTarget, collect)(RowComponent);