import React, {Component} from 'react';
import PropTypes from "prop-types";
import {Popup} from "semantic-ui-react";
import ProjectActions from "../../../../../../Actions/Project.actions";

class ToolbarActionsMergeAndAlignComponent extends Component {

    static propTypes = {
        selection: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {
            type: 'merge&align'
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
        if (this.props.selection.count > 2
            && this.props.selection.source.count > 0
            && this.props.selection.target.count > 0) {
            disabled = false;
        }
        return <button
            disabled={disabled}
            onMouseOut={this.onMouseLeave}
            onMouseOver={this.onHover}
            onClick={this.onClick}>
            <i className={classes.join(" ")}></i>
            Merge and Align
        </button>;
        /* return (
             <Popup trigger={comp} content='shortcut alt+A' on='hover' inverted/>
         );
         */
    }

    onClick = () => {
        ProjectActions.mergeAndAlignSegments(this.props.selection);
        ProjectActions.onActionHover(null);
        ProjectActions.onActionHover(null);
    };

    onHover = () => {
        ProjectActions.onActionHover(this.state.type);
    };

    onMouseLeave = () => {
        ProjectActions.onActionHover(null);
    };

}

export default ToolbarActionsMergeAndAlignComponent;