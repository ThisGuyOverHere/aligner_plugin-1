import React, {Component} from 'react';
import PropTypes from "prop-types";
import {Popup} from "semantic-ui-react";
import ProjectActions from "../../../../../../Actions/Project.actions";
import {getSegmentByIndex, getSegmentIndexByOrder} from "../../../../../../Helpers/SegmentUtils.helper";

class ToolbarActionsAlignComponent extends Component {

    static propTypes = {
        selection: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {
            type: 'align'
        };
    }


    componentDidMount() {
    }

    componentWillUnmount() {
    }

    render() {
        //check status of split action
        let disabled = true;
        let classes = ['icon', 'check'];
        if (this.props.selection.source.count === 1
            && this.props.selection.target.count === 1) {
            disabled = false;
        }
        return <button
            disabled={disabled}
            onMouseOut={this.onMouseLeave}
            onMouseOver={this.onHover}
            onClick={this.onClick}>
            Align
        </button>;
        /*return (
            <Popup trigger={comp} content='shortcut alt+A' on='hover' inverted/>
        );*/
    }

    onClick = () => {
        const sourceIndex = getSegmentIndexByOrder(this.props.selection.source.list[0], 'source');
        const targetToOrder = getSegmentByIndex(sourceIndex, 'target').order;

        const log = {
            type: 'target',
            from: this.props.selection.target.list[0],
            to: targetToOrder
        };

        ProjectActions.requireChangeSegmentPosition(log);
        ProjectActions.addSegmentToSelection(-1);
        ProjectActions.onActionHover(null);
    };

    onHover = () => {
        ProjectActions.onActionHover(this.state.type);
    };

    onMouseLeave = () => {
        ProjectActions.onActionHover(null);
    };

}

export default ToolbarActionsAlignComponent;