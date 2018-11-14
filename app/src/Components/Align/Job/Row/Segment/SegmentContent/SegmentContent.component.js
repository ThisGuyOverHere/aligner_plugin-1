import React, {Component} from 'react';
import PropTypes from "prop-types";
import ProjectStore from "../../../../../../Stores/Project.store";
import ProjectConstants from "../../../../../../Constants/Project.constants";

class SegmentContentComponent extends Component {
    static propTypes = {
        content: PropTypes.string,
        id: PropTypes.any
    };

    constructor(props) {
        super(props);
        this.state = {
            content: this.props.content,
            lastSearchContent: this.props.content,
            id: this.props.id,
            searchOn: false
        }
    }

    static getDerivedStateFromProps(props, state) {
        state.content = props.content;
        state.id = props.id;
        return state;
    }

    componentDidMount() {
        ProjectStore.addListener(ProjectConstants.SEARCH_RESULTS, this.onSearchEmit);
    }

    componentWillUnmount() {
        ProjectStore.removeListener(ProjectConstants.SEARCH_RESULTS, this.onSearchEmit);
    }


    render = () => {
        const content = this.state.searchOn ? this.state.lastSearchContent : this.state.content;
        return <p dangerouslySetInnerHTML={{__html: content}}>
        </p>
    };

    onSearchEmit = (search) => {
        const {id, content} = this.state;
        let searchOn = false;
        let lastSearchContent = content;
        if (search && search.idList.indexOf(id) >= 0) {
            const startIndex = content.toLowerCase().indexOf(search.q);
            const endIndex = startIndex + search.q.length;
            console.log(startIndex, startIndex + search.q.length);
            console.log(lastSearchContent);
            lastSearchContent = lastSearchContent.slice(0, endIndex)+"</mark>"+lastSearchContent.slice(endIndex);
            lastSearchContent = lastSearchContent.slice(0, startIndex)+"<mark>"+lastSearchContent.slice(startIndex);
            searchOn = true;
        }

        this.setState({
            searchOn: searchOn,
            lastSearchContent: lastSearchContent
        });
    }

}

export default SegmentContentComponent;
