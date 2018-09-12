import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectActions from "../../../../../Actions/Project.actions";
import {getSegmentByOrder} from "../../../../../Helpers/SegmentUtils.helper";
import {Popup} from "semantic-ui-react";

class ToolbarActionsMergeComponent extends Component {

    static propTypes = {
        selection: PropTypes.object.isRequired
    };

    constructor(props) {
        super(props);
        this.state = {};
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
        const comp = <span><button
            disabled={disabled}
            onClick={this.onMergeClick}>
            <i className={mergeClasses.join(" ")}></i>
            merge
        </button></span>;
        return (
            <Popup trigger={comp} content='shortcut alt+M' on='hover' inverted/>
        );
    }

    onMergeClick = () => {
        const type = this.props.selection.source.count > 0 ? 'source' : 'target';
        let targets = [];
        let source = {};
        this.props.selection[type].list.reverse().map((e, index) => {
            const segment = getSegmentByOrder(this.props.selection[type].list[index], type);
            if (index > 0) {
                targets.push(segment)
            } else {
                source = segment;
            }
        });
        ProjectActions.mergeSegments(targets, source);
        ProjectActions.addSegmentToSelection(-1);
    };

}

export default ToolbarActionsMergeComponent;