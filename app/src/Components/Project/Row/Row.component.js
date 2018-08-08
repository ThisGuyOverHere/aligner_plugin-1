import React, {Component} from 'react';
import {ItemTypes} from '../../../Constants/Draggable.constants';
import {DropTarget} from 'react-dnd';
import ProjectActions from "../../../Actions/Project.actions";
import PropTypes from "prop-types";
import HomeComponent from "../../Home/Home.component";

const RowTarget = {
    canDrop(props, monitor) {
        return true
    },
    drop(props, monitor) {
        const from = monitor.getItem();
        const types = {
            0: "source",
            1: "target"
        };
        const log = {
            type: types[from.type],
            from: from.segment.order,
            to: props.children[from.type].props.segment.order
        };
        ProjectActions.changeSegmentPosition(log);
    }
};

function collect(connect, monitor) {
    return {
        connectDropTarget: connect.dropTarget(),
        isOver: monitor.isOver(),
        canDrop: monitor.canDrop()
    }
}


class RowComponent extends Component {
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

    }

    render() {
        let rowClass = ['project-row'];

        const {connectDropTarget, isOver, canDrop} = this.props;
        if (isOver) {
            rowClass.push('dropHover');
        }
        if(!canDrop){
            rowClass.push('notDropStatus');
        }
        return connectDropTarget(
            <div className={rowClass.join(' ')}>
                <div className="ui grid middle aligned">
                    <div className="one wide column center aligned">
                        {this.props.index}
                    </div>
                    <div className="seven wide column">
                        {this.props.children[0]}
                    </div>
                    <div className="one wide column center aligned"> </div>
                    <div className="seven wide column">
                        {this.props.children[1]}
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

RowComponent.propTypes = {
    index: PropTypes.number.isRequired,
    row: PropTypes.object.isRequired
};

export default DropTarget(ItemTypes.ITEM, RowTarget, collect)(RowComponent);