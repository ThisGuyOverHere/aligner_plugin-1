import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../../Actions/Project.actions";
import {Popup} from "semantic-ui-react";

class ToolbarActionsMergeComponent extends Component {

    static propTypes = {
        selection: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {
            type: 'merge'
        };
    }


    componentDidMount() {
    }

    componentWillUnmount() {
    }

    render() {
        //check status of merge action
        let disabled = false;
        let mergeClasses = ['icon', 'random'];
        if (
            !((this.props.selection.source.count === 0 && this.props.selection.target.count > 1)
                || (this.props.selection.target.count === 0 && this.props.selection.source.count > 1))
        ) {
            disabled = true;
        }
        return <span><button
            disabled={disabled}
            onMouseOut={this.onMouseLeave}
            onMouseOver={this.onHover}
            onClick={this.onMergeClick}>
            <i className={mergeClasses.join(" ")}></i>
            merge
        </button></span>;
        /*
         return (
        <Popup trigger={comp} content='shortcut alt+M' on='hover' inverted/>
    );
    */

    }

    onMergeClick = () => {
        const type = this.props.selection.source.count > 0 ? 'source' : 'target';
        const segments = this.props.selection[type].list.sort();
        ProjectActions.mergeSegments(segments, type);
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

export default ToolbarActionsMergeComponent;